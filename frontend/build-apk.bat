@echo off
REM Script para compilar APK con Java 17, Angular y Capacitor
setlocal enabledelayedexpansion

set JAVA_HOME=C:\Program Files\Java\jdk-17
set PATH=%JAVA_HOME%\bin;%PATH%

REM Verifica que Java esté configurado correctamente
echo.
echo Verificando Java:
java -version
echo.

REM Cambia al directorio del frontend de Angular
cd /d "%~dp0"

REM 1. Compilar el proyecto de Angular
echo.
echo ==============================================
echo 📦 Compilando el proyecto de Angular...
echo ==============================================
call npm run build
if %errorlevel% neq 0 (
    echo.
    echo ❌ Error al compilar Angular
    pause
    exit /b %errorlevel%
)

REM 2. Sincronizar con Capacitor (copiar archivos a Android)
echo.
echo ==============================================
echo 🔄 Sincronizando con Capacitor...
echo ==============================================
call npx cap sync
if %errorlevel% neq 0 (
    echo.
    echo ❌ Error al sincronizar con Capacitor
    pause
    exit /b %errorlevel%
)

REM Cambia al directorio del proyecto Android
cd /d "%~dp0android"

REM 3. Limpia y compila el APK
echo.
echo ==============================================
echo 📱 Compilando APK con Gradle...
echo ==============================================
call .\gradlew.bat clean assembleDebug -x lint

REM Si la compilación fue exitosa, muestra la ruta del APK
if %errorlevel% equ 0 (
    echo.
    echo ✅ APK compilada exitosamente!
    echo.
    echo Ubicación: %~dp0android\app\build\outputs\apk\debug\app-debug.apk
    echo.
    echo Puedes enviar este archivo por WhatsApp o AirDrop
) else (
    echo.
    echo ❌ Error en la compilación del APK
)

pause
