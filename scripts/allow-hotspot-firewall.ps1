# Endesha kama Administrator: click-right PowerShell → Run as administrator
# Ruhusu simu kwenye WiFi kufikia Laravel server (port 8000)

$ruleName = "Tech-Sphere Hotspot 8000"

$existing = Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue
if ($existing) {
    Write-Host "Rule tayari ipo: $ruleName"
} else {
    New-NetFirewallRule `
        -DisplayName $ruleName `
        -Direction Inbound `
        -Protocol TCP `
        -LocalPort 8000 `
        -Action Allow `
        -Profile Any | Out-Null
    Write-Host "Firewall rule imeongezwa: port 8000 inaruhusiwa."
}

Write-Host ""
Write-Host "Jaribu kwenye simu (kwenye WiFi ya hotspot):"
Write-Host "http://192.168.10.254:8000"
