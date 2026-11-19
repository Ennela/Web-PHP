<#
.SYNOPSIS
  Sao luu DB MySQL + anh upload cua stack Docker Compose ra thu muc NGOAI repo.

.DESCRIPTION
  Moi lan chay tao 2 file: shop-<yyyyMMdd-HHmmss>-db.sql.gz va shop-<...>-uploads.tgz,
  roi chi giu -Keep ban moi nhat. Mat khau DB doc tu bien moi truong ben trong container
  (khong di qua dong lenh / log tren may host). Dump xong duoc kiem tra (dong cuoi 'Dump completed',
  gzip -t) truoc khi doi ten; loi thi xoa file do dang va thoat ma 1.

  KHONG sao luu khoa ma hoa (APP_ENCRYPTION_KEY) va file .env: thieu khoa thi du lieu ca nhan trong
  ban sao luu KHONG giai ma duoc. Giu khoa o noi khac (vd trinh quan ly mat khau), khong de chung thu muc.

.PARAMETER OutDir   Thu muc luu (mac dinh D:\backup\shop). Nen la o dia/may khac repo.
.PARAMETER Keep     So ban giu lai (mac dinh 14).
.PARAMETER EnvFile  File bien moi truong cua compose (mac dinh .env.docker; bo qua neu khong ton tai).

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File scripts\backup_db.ps1

.NOTES
  DANG KY CHAY HANG NGAY (Task Scheduler, 02:30):
    schtasks /Create /TN "ShopBackup" /SC DAILY /ST 02:30 /TR "powershell -NoProfile -ExecutionPolicy Bypass -File D:\sources\repos\htdocs\WEB-PHP\scripts\backup_db.ps1"
  (Docker Desktop phai dang chay; neu may tat luc 02:30, bat 'Run task as soon as possible after a scheduled start is missed'.)

  KHOI PHUC (chay tu thu muc goc repo, thay <ts> bang moc thoi gian cua ban sao luu):
    # 1) DB vao mot DB tam de kiem tra
    docker compose --env-file .env.docker cp D:\backup\shop\shop-<ts>-db.sql.gz db:/tmp/restore.sql.gz
    docker compose --env-file .env.docker exec -T db sh -c 'export MYSQL_PWD=$MYSQL_ROOT_PASSWORD; mysql -uroot -e "CREATE DATABASE restore_test CHARACTER SET utf8mb4"; gzip -dc /tmp/restore.sql.gz | mysql -uroot restore_test; rm /tmp/restore.sql.gz'
    # 2) Khoi phuc that: thay restore_test bang giaythethao2 (DROP/CREATE lai DB truoc, dung app truoc: docker compose stop app)
    # 3) Anh upload
    docker compose --env-file .env.docker cp D:\backup\shop\shop-<ts>-uploads.tgz app:/tmp/u.tgz
    docker compose --env-file .env.docker exec -T app sh -c 'tar -xzf /tmp/u.tgz -C /var/www/html && rm /tmp/u.tgz'
#>
param(
    [string]$OutDir = 'D:\backup\shop',
    [int]$Keep = 14,
    [string]$EnvFile = '.env.docker'
)
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$compose = @('compose')
if (Test-Path -LiteralPath $EnvFile) { $compose += @('--env-file', $EnvFile) }

function Invoke-Docker {
    param([string[]]$DockerArgs)
    & docker @compose @DockerArgs
    if ($LASTEXITCODE -ne 0) { throw "docker $($DockerArgs -join ' ') loi (ma $LASTEXITCODE)" }
}

if ($Keep -lt 1) { throw 'Keep phai >= 1' }
New-Item -ItemType Directory -Force -Path $OutDir | Out-Null
$ts = Get-Date -Format 'yyyyMMdd-HHmmss'
$dbFile = Join-Path $OutDir "shop-$ts-db.sql.gz"
$upFile = Join-Path $OutDir "shop-$ts-uploads.tgz"
$parts = @("$dbFile.part", "$upFile.part")

try {
    # --- DB: dump trong container (mat khau lay tu env cua container), kiem tra roi moi nen
    Invoke-Docker @('exec', '-T', 'db', 'sh', '-c',
        'export MYSQL_PWD=$MYSQL_ROOT_PASSWORD; mysqldump -uroot --single-transaction --routines --triggers --default-character-set=utf8mb4 --result-file=/tmp/db.sql giaythethao2')
    Invoke-Docker @('exec', '-T', 'db', 'sh', '-c',
        'tail -n 1 /tmp/db.sql | grep -q ''Dump completed'' && gzip -f /tmp/db.sql && gzip -t /tmp/db.sql.gz')
    Invoke-Docker @('cp', 'db:/tmp/db.sql.gz', "$dbFile.part")
    Invoke-Docker @('exec', '-T', 'db', 'rm', '-f', '/tmp/db.sql', '/tmp/db.sql.gz')

    # --- Anh upload (2 volume gan vao app)
    Invoke-Docker @('exec', '-T', 'app', 'sh', '-c',
        'tar -czf /tmp/uploads.tgz -C /var/www/html admin/uploads auth/uploads && gzip -t /tmp/uploads.tgz')
    Invoke-Docker @('cp', 'app:/tmp/uploads.tgz', "$upFile.part")
    Invoke-Docker @('exec', '-T', 'app', 'rm', '-f', '/tmp/uploads.tgz')

    foreach ($p in $parts) {
        if (-not (Test-Path -LiteralPath $p) -or (Get-Item -LiteralPath $p).Length -lt 1KB) { throw "File sao luu rong/thieu: $p" }
    }
    Move-Item -LiteralPath "$dbFile.part" -Destination $dbFile
    Move-Item -LiteralPath "$upFile.part" -Destination $upFile
}
catch {
    foreach ($p in $parts) { Remove-Item -LiteralPath $p -Force -ErrorAction SilentlyContinue }
    Write-Error "SAO LUU THAT BAI: $($_.Exception.Message)"
    exit 1
}

# --- Giu -Keep ban moi nhat (ten co moc thoi gian nen sap xep theo ten)
Get-ChildItem -LiteralPath $OutDir -Filter 'shop-*-db.sql.gz' | Sort-Object Name -Descending |
    Select-Object -Skip $Keep | ForEach-Object {
        $stamp = $_.Name -replace '^shop-(.+)-db\.sql\.gz$', '$1'
        Remove-Item -LiteralPath $_.FullName -Force
        Remove-Item -LiteralPath (Join-Path $OutDir "shop-$stamp-uploads.tgz") -Force -ErrorAction SilentlyContinue
    }

'{0}: OK  db={1:N0} B  uploads={2:N0} B  -> {3}' -f $ts, (Get-Item $dbFile).Length, (Get-Item $upFile).Length, $OutDir
