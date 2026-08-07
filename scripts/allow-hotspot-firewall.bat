@echo off
echo Endesha kama Administrator!
echo.
netsh advfirewall firewall add rule name="Tech-Sphere Hotspot 8000" dir=in action=allow protocol=TCP localport=8000
echo.
echo Ikiwa imefanikiwa, jaribu kwenye simu: http://192.168.10.254:8000
pause
