@echo off
REM Script para compilar APK con Java 17
setlocal enabledelayedexpansion

set JAVA_HOME=C:\Program Files\Java\jdk-17
set PATH=%JAVA_HOME%\bin;%PATH%

REM Verifica que Java esté configurado correctamente
echo.
echo Verificando Java:
java -version
echo.

REM Cambia al directorio del proyecto
cd /d C:\Users\aleja\Desktop\eie\frontend\android

REM Limpia y compila
echo Compilando APK...
call .\gradlew.bat clean assembleDebug -x lint

REM Si la compilación fue exitosa, muestra la ruta del APK
if %errorlevel% equ 0 (
    echo.
    echo ✅ APK compilada exitosamente!
    echo.
    echo Ubicación: C:\Users\aleja\Desktop\eie\frontend\android\app\build\outputs\apk\debug\app-debug.apk
    echo.
    echo Puedes enviar este archivo por WhatsApp o AirDrop
) else (
    echo.
    echo ❌ Error en la compilación
)

pause
