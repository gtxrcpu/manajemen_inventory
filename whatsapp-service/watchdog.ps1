$ErrorActionPreference = 'Stop'

$serviceDir = $PSScriptRoot
$appRoot = Split-Path -Parent $serviceDir
$envFile = Join-Path $serviceDir '.env'
$logDir = Join-Path $serviceDir 'logs'
$watchdogLog = Join-Path $logDir 'watchdog.log'
$stdoutLog = Join-Path $serviceDir 'service.stdout.log'
$stderrLog = Join-Path $serviceDir 'service.stderr.log'
$queueStdoutLog = Join-Path $serviceDir 'queue-worker.stdout.log'
$queueStderrLog = Join-Path $serviceDir 'queue-worker.stderr.log'
$nodePath = (Get-Command 'node.exe' -ErrorAction Stop).Source
$phpPath = (Get-Command 'php.exe' -ErrorAction Stop).Source
$servicePort = 3100
$serviceToken = ''
$queueWorkerName = 'technolife-inventory-whatsapp'
$watchdogMutex = [System.Threading.Mutex]::new($false, 'Local\TechnolifeWhatsAppServiceWatchdog')
if (-not $watchdogMutex.WaitOne(0)) { exit 0 }

New-Item -ItemType Directory -Path $logDir -Force | Out-Null
foreach ($line in Get-Content -LiteralPath $envFile) {
    if ($line -match '^\s*WHATSAPP_PORT=(.*)$') { $servicePort = [int]$Matches[1].Trim().Trim('"').Trim("'") }
    if ($line -match '^\s*WHATSAPP_SERVICE_TOKEN=(.*)$') { $serviceToken = $Matches[1].Trim().Trim('"').Trim("'") }
}

function Write-WatchdogLog([string] $message) {
    Add-Content -LiteralPath $watchdogLog -Value "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') $message"
}

function Get-ServiceStatus {
    if ($serviceToken.Length -lt 32) { return $null }
    try {
        return Invoke-RestMethod -Uri "http://127.0.0.1:$servicePort/status" -Headers @{ Authorization = "Bearer $serviceToken" } -TimeoutSec 3
    } catch {
        return $null
    }
}

function Ensure-QueueWorker {
    $worker = Get-CimInstance Win32_Process | Where-Object {
        $_.Name -eq 'php.exe' -and $_.CommandLine -match [regex]::Escape("--name=$queueWorkerName")
    } | Select-Object -First 1
    if ($worker) { return }

    foreach ($logPath in @($queueStdoutLog, $queueStderrLog)) {
        if (Test-Path -LiteralPath $logPath) {
            $logItem = Get-Item -LiteralPath $logPath
            if ($logItem.Length -gt 0) {
                $archivePath = "$logPath.$(Get-Date -Format 'yyyyMMdd-HHmmss').bak"
                Move-Item -LiteralPath $logPath -Destination $archivePath -Force
            }
        }
    }

    try {
        $arguments = "artisan queue:work database --name=$queueWorkerName --queue=default --sleep=2 --tries=3 --timeout=75 --max-time=3600"
        $workerProcess = Start-Process -FilePath $phpPath -ArgumentList $arguments -WorkingDirectory $appRoot -WindowStyle Hidden -RedirectStandardOutput $queueStdoutLog -RedirectStandardError $queueStderrLog -PassThru
        Write-WatchdogLog "Started Laravel queue worker process $($workerProcess.Id)."
    } catch {
        Write-WatchdogLog "Could not start Laravel queue worker: $($_.Exception.Message)"
    }
}

$stuckSince = $null
while ($true) {
    Ensure-QueueWorker
    $status = Get-ServiceStatus
    if ($status) {
        if (-not $status.approved -or $status.connected -or $status.qrReady) {
            $stuckSince = $null
            Start-Sleep -Seconds 10
            continue
        }

        if (-not $stuckSince) { $stuckSince = Get-Date }
        if (((Get-Date) - $stuckSince).TotalSeconds -ge 120) {
            $listener = Get-NetTCPConnection -State Listen -LocalPort $servicePort -ErrorAction SilentlyContinue | Select-Object -First 1
            if ($listener -and (Get-Process -Id $listener.OwningProcess -ErrorAction SilentlyContinue)) {
                Write-WatchdogLog "WhatsApp remained in '$($status.state)' without a QR for 120 seconds; restarting service process $($listener.OwningProcess)."
                Stop-Process -Id $listener.OwningProcess -Force -ErrorAction SilentlyContinue
            }
            $stuckSince = Get-Date
        }
        Start-Sleep -Seconds 10
        continue
    }

    if ($serviceToken.Length -lt 32) {
        Write-WatchdogLog 'Service token missing or too short; retrying after 60 seconds.'
        Start-Sleep -Seconds 60
        continue
    }

    foreach ($logPath in @($stdoutLog, $stderrLog)) {
        if (Test-Path -LiteralPath $logPath) {
            $logItem = Get-Item -LiteralPath $logPath
            if ($logItem.Length -gt 0) {
                $archivePath = "$logPath.$(Get-Date -Format 'yyyyMMdd-HHmmss').bak"
                Move-Item -LiteralPath $logPath -Destination $archivePath -Force
            }
        }
    }

    $listener = Get-NetTCPConnection -State Listen -LocalPort $servicePort -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($listener) {
        if (-not $stuckSince) { $stuckSince = Get-Date }
        if (((Get-Date) - $stuckSince).TotalSeconds -ge 90) {
            Write-WatchdogLog "Service process $($listener.OwningProcess) did not answer its authenticated status endpoint for 90 seconds; restarting it."
            Stop-Process -Id $listener.OwningProcess -Force -ErrorAction SilentlyContinue
            $stuckSince = Get-Date
        }
        Start-Sleep -Seconds 10
        continue
    }

    try {
        $child = Start-Process -FilePath $nodePath -ArgumentList 'src/server.js' -WorkingDirectory $serviceDir -WindowStyle Hidden -RedirectStandardOutput $stdoutLog -RedirectStandardError $stderrLog -PassThru
        Write-WatchdogLog "Started WhatsApp service process $($child.Id)."
        $stuckSince = Get-Date
    } catch {
        Write-WatchdogLog "Could not start WhatsApp service: $($_.Exception.Message)"
    }

    Start-Sleep -Seconds 5
}
