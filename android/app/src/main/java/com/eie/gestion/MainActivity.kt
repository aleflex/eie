package com.eie.gestion

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.eie.gestion.data.repository.AppRepository
import com.eie.gestion.data.repository.SessionManager
import com.eie.gestion.ui.screens.AdminScreen
import com.eie.gestion.ui.screens.DocenteScreen
import com.eie.gestion.ui.screens.LoginScreen
import com.eie.gestion.ui.screens.StudentScreen
import com.eie.gestion.ui.theme.EieTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        val sessionManager = SessionManager(this)
        val repository = AppRepository(sessionManager)
        
        setContent {
            EieTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    AppNavigation(sessionManager, repository)
                }
            }
        }
    }
}

@Composable
fun AppNavigation(sessionManager: SessionManager, repository: AppRepository) {
    val navController = rememberNavController()
    
    // Determinar la ruta de inicio basada en si hay sesión y cuál es el rol
    val startDestination = remember {
        val token = sessionManager.fetchAuthToken()
        val role = sessionManager.fetchUserRole()
        
        if (!token.isNullOrEmpty() && !role.isNullOrEmpty()) {
            when (role.lowercase()) {
                "admin" -> "admin"
                "docente" -> "docente"
                "estudiante" -> "student"
                else -> "login"
            }
        } else {
            "login"
        }
    }

    NavHost(navController = navController, startDestination = startDestination) {
        composable("login") {
            LoginScreen(
                sessionManager = sessionManager,
                repository = repository,
                onLoginSuccess = { role ->
                    val dest = when (role.lowercase()) {
                        "admin" -> "admin"
                        "docente" -> "docente"
                        "estudiante" -> "student"
                        else -> "login"
                    }
                    navController.navigate(dest) {
                        popUpTo("login") { inclusive = true }
                    }
                }
            )
        }
        
        composable("admin") {
            AdminScreen(
                repository = repository,
                onLogout = {
                    sessionManager.clearSession()
                    navController.navigate("login") {
                        popUpTo("admin") { inclusive = true }
                    }
                }
            )
        }
        
        composable("docente") {
            val userId = remember {
                // mis-paralelos en el backend espera el id_usuario en el query string.
                // Obtenemos el id del usuario desde el backend, pero necesitamos saber si guardamos el idUsuario del User.
                // En AuthController, response.user contiene el idUsuario en "id_usuario".
                // Busquemos el token y datos del usuario.
                // Para esto, guardamos el correo y nombre, pero necesitamos el id_usuario!
                // Espera, ¿guardamos id_usuario?
                // En SessionManager, no tenemos KEY_USER_ID, sino KEY_DOCENTE_ID y KEY_ESTUDIANTE_ID.
                // Pero en AuthController.php (misParalelos):
                // $userId = $request->input('user_id'); // que es id_usuario!
                // ¿Y en login qué se retorna?
                // En AuthController login():
                // $userData = $user->toArray();
                // $userData['docente_id'] = $docente->id_docente;
                // $userData['rol'] = 'docente';
                // response()->json([ ..., 'user' => $userData, ... ])
                // O sea, response.user tiene 'id_usuario' y 'docente_id'.
                // Así que guardaremos el id_usuario en SessionManager para pasarlo a misParalelos!
                // ¡Esto es un detalle fundamental!
            }
            
            // Vamos a obtener el idUsuario de SessionManager.
            // Para asegurar esto, debemos modificar SessionManager para soportar guardar y recuperar el idUsuario!
            // Sí, agreguemos KEY_USER_ID a SessionManager y guardémoslo en LoginScreen.
            // Hagamos esa pequeña modificación ahora.
            val userIdVal = remember { sessionManager.fetchUserId() }
            DocenteScreen(
                userId = userIdVal,
                repository = repository,
                onLogout = {
                    sessionManager.clearSession()
                    navController.navigate("login") {
                        popUpTo("docente") { inclusive = true }
                    }
                }
            )
        }
        
        composable("student") {
            val estudianteIdVal = remember { sessionManager.fetchEstudianteId() }
            StudentScreen(
                estudianteId = estudianteIdVal,
                repository = repository,
                onLogout = {
                    sessionManager.clearSession()
                    navController.navigate("login") {
                        popUpTo("student") { inclusive = true }
                    }
                }
            )
        }
    }
}
