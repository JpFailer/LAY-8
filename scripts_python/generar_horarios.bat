@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"

set "PY=python"
if exist "venv\Scripts\python.exe" set "PY=venv\Scripts\python.exe"

echo ========================================================
echo  LAY-8 - Generador local de horarios JSON (CGD-001)
echo ========================================================
echo Usando interprete: %PY%
echo.

if not "%~1"=="" (
    set "CAR=%~2"
    if "%CAR%"=="" set "CAR=ING-INF"
    echo Procesando archivo individual: "%~1" [!CAR!]
    "%PY%" procesar_horario_cgd001.py "%~1" "!CAR!" > nul
    if errorlevel 1 (
        echo ERROR al procesar "%~1".
        exit /b 1
    )
    echo Listo: horarios_procesados_!CAR!.json actualizado.
    exit /b 0
)

for %%C in (ING-INF ING-MAR ING-AMB ADM TUR) do (
    if exist "horarios\horario_%%C.pdf" (
        echo Procesando %%C desde horarios\horario_%%C.pdf ...
        "%PY%" procesar_horario_cgd001.py "horarios\horario_%%C.pdf" "%%C" > nul
        if errorlevel 1 (
            echo  [ERROR] Fallo %%C
        ) else (
            echo  [OK] Generado horarios_procesados_%%C.json
        )
    ) else if "%%C"=="ING-INF" if exist "horario.pdf" (
        echo Procesando ING-INF desde horario.pdf ...
        "%PY%" procesar_horario_cgd001.py "horario.pdf" "ING-INF" > nul
        if errorlevel 1 (
            echo  [ERROR] Fallo ING-INF
        ) else (
            echo  [OK] Generado horarios_procesados_ING-INF.json
        )
    ) else (
        echo  [-] Sin PDF para %%C, omitido.
    )
)

echo.
echo Proceso finalizado. Ya puedes subir los .json desde Panel Admin -> Horarios.
pause
