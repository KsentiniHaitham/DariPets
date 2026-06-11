# =====================================================================
#  DariPets - Lance le backend (Symfony) ET le frontend (Vite) d'un coup
#  Usage :  clic droit > "Executer avec PowerShell"   ou   .\start-dev.ps1
# =====================================================================
$ErrorActionPreference = "Stop"
$root = $PSScriptRoot

Write-Host ""
Write-Host "==============================================" -ForegroundColor Magenta
Write-Host "   DariPets - demarrage de l'environnement dev" -ForegroundColor Magenta
Write-Host "==============================================" -ForegroundColor Magenta

# --- Backend Symfony (daemon, port 8004, sans TLS pour le proxy Vite) ---
# XDEBUG_MODE=off : Xdebug multiplie les temps de reponse par ~20 en dev
$env:XDEBUG_MODE = "off"
Write-Host "`n[1/2] Backend Symfony  -> http://127.0.0.1:8004 (Xdebug off)" -ForegroundColor Cyan
Push-Location $root
symfony server:start -d --no-tls --port=8004
Pop-Location

# --- Frontend Vite (premier plan : Ctrl+C l'arrete) ---
Write-Host "[2/2] Frontend Vite    -> http://localhost:5173" -ForegroundColor Cyan
Write-Host "`nOuvre :  http://localhost:5173" -ForegroundColor Green
Write-Host "(Ctrl+C arrete le frontend ; le backend tourne en tache de fond.)" -ForegroundColor DarkGray
Write-Host "Pour arreter le backend ensuite :  symfony server:stop`n" -ForegroundColor DarkGray

Push-Location (Join-Path $root "frontend")
npm run dev
Pop-Location
