package com.eie.gestion.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Dns
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.outlined.Email
import androidx.compose.material.icons.outlined.Lock
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.eie.gestion.data.model.LoginRequest
import com.eie.gestion.data.repository.AppRepository
import com.eie.gestion.data.repository.SessionManager
import com.eie.gestion.ui.theme.EmiBlue
import com.eie.gestion.ui.theme.EmiYellow
import kotlinx.coroutines.launch

@OptIn(Material3Api::class)
@Composable
fun LoginScreen(
    sessionManager: SessionManager,
    repository: AppRepository,
    onLoginSuccess: (String) -> Unit
) {
    val coroutineScope = rememberCoroutineScope()
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf<String?>(null) }
    var showApiDialog by remember { mutableStateOf(false) }

    val gradientBackground = Brush.verticalGradient(
        colors = listOf(Color.White, Color(0xFFF4F7F9))
    )

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(gradientBackground),
        contentAlignment = Alignment.Center
    ) {
        // Botón de configuración de API flotante arriba a la derecha
        IconButton(
            onClick = { showApiDialog = true },
            modifier = Modifier
                .align(Alignment.TopEnd)
                .padding(16.dp)
        ) {
            Icon(
                imageVector = Icons.Default.Settings,
                contentDescription = "Configurar Servidor",
                tint = EmiBlue
            )
        }

        Card(
            modifier = Modifier
                .fillMaxWidth(0.85f)
                .wrapContentHeight(),
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White),
            elevation = CardDefaults.cardElevation(8.dp)
        ) {
            Column(
                modifier = Modifier
                    .padding(24.dp)
                    .fillMaxWidth(),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                // Header (Branding)
                Box(
                    modifier = Modifier
                        .size(80.dp)
                        .clip(RoundedCornerShape(40.dp))
                        .background(EmiBlue),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = "EIE",
                        color = Color.White,
                        fontSize = 28.sp,
                        fontWeight = FontWeight.Bold
                    )
                }

                Spacer(modifier = Modifier.height(16.dp))

                Text(
                    text = "SISTEMA DE GESTIÓN",
                    fontSize = 20.sp,
                    fontWeight = FontWeight.Bold,
                    color = EmiBlue,
                    textAlign = TextAlign.Center
                )
                Text(
                    text = "Escuela de Idiomas del Ejército",
                    fontSize = 13.sp,
                    color = Color.Gray,
                    textAlign = TextAlign.Center
                )

                Spacer(modifier = Modifier.height(24.dp))

                // Inputs
                OutlinedTextField(
                    value = email,
                    onValueChange = { email = it },
                    label = { Text("Correo Electrónico") },
                    placeholder = { Text("admin@eie.edu.bo") },
                    leadingIcon = { Icon(Icons.Outlined.Email, contentDescription = null, tint = EmiBlue) },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = EmiBlue,
                        focusedLabelColor = EmiBlue
                    ),
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email)
                )

                Spacer(modifier = Modifier.height(16.dp))

                OutlinedTextField(
                    value = password,
                    onValueChange = { password = it },
                    label = { Text("Contraseña") },
                    leadingIcon = { Icon(Icons.Outlined.Lock, contentDescription = null, tint = EmiBlue) },
                    singleLine = true,
                    visualTransformation = PasswordVisualTransformation(),
                    modifier = Modifier.fillMaxWidth(),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = EmiBlue,
                        focusedLabelColor = EmiBlue
                    ),
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password)
                )

                Spacer(modifier = Modifier.height(16.dp))

                if (errorMessage != null) {
                    Text(
                        text = errorMessage!!,
                        color = Color.Red,
                        fontSize = 13.sp,
                        modifier = Modifier.padding(vertical = 4.dp),
                        textAlign = TextAlign.Center
                    )
                }

                Spacer(modifier = Modifier.height(8.dp))

                Button(
                    onClick = {
                        if (email.isNotEmpty() && password.isNotEmpty()) {
                            isLoading = true
                            errorMessage = null
                            coroutineScope.launch {
                                val result = repository.login(LoginRequest(email, password))
                                isLoading = false
                                result.fold(
                                    onSuccess = { response ->
                                        // Guardar sesión
                                        val user = response.user
                                        sessionManager.saveAuthToken(response.token)
                                        sessionManager.saveUserRole(user.rol ?: "admin")
                                        sessionManager.saveUserId(user.idUsuario)
                                        sessionManager.saveUserEmail(user.correoInstitucional)
                                        sessionManager.saveUserName(user.name ?: "")
                                        user.docenteId?.let { sessionManager.saveDocenteId(it) }
                                        user.estudianteId?.let { sessionManager.saveEstudianteId(it) }

                                        onLoginSuccess(user.rol ?: "admin")
                                    },
                                    onFailure = { error ->
                                        errorMessage = error.message ?: "Error al conectar con el servidor"
                                    }
                                )
                            }
                        } else {
                            errorMessage = "Por favor, completa todos los campos"
                        }
                    },
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(50.dp),
                    shape = RoundedCornerShape(8.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = EmiBlue),
                    disabledContainerColor = Color.Gray,
                    enabled = !isLoading
                ) {
                    if (isLoading) {
                        CircularProgressIndicator(color = Color.White, modifier = Modifier.size(24.dp))
                    } else {
                        Text(
                            text = "INICIAR SESIÓN",
                            fontWeight = FontWeight.Bold,
                            color = Color.White
                        )
                    }
                }
            }
        }

        // Modal de Configuración de API
        if (showApiDialog) {
            var inputUrl by remember { mutableStateOf(sessionManager.fetchApiUrl()) }
            AlertDialog(
                onDismissRequest = { showApiDialog = false },
                title = {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(Icons.Default.Dns, contentDescription = null, tint = EmiYellow)
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Configuración de Servidor")
                    }
                },
                text = {
                    Column {
                        Text(
                            "Introduce la URL base del backend de Laravel que se ejecuta en Docker.",
                            fontSize = 13.sp,
                            color = Color.Gray
                        )
                        Spacer(modifier = Modifier.height(12.dp))
                        OutlinedTextField(
                            value = inputUrl,
                            onValueChange = { inputUrl = it },
                            label = { Text("URL del Servidor") },
                            placeholder = { Text("Ej: http://10.0.2.2:8000") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth()
                        )
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            "* En el emulador de Android Studio utiliza: http://10.0.2.2:8000",
                            fontSize = 11.sp,
                            color = EmiBlue
                        )
                    }
                },
                confirmButton = {
                    Button(
                        onClick = {
                            sessionManager.saveApiUrl(inputUrl)
                            showApiDialog = false
                        },
                        colors = ButtonDefaults.buttonColors(containerColor = EmiBlue)
                    ) {
                        Text("Guardar", color = Color.White)
                    }
                },
                dismissButton = {
                    TextButton(onClick = { showApiDialog = false }) {
                        Text("Cancelar", color = EmiBlue)
                    }
                }
            )
        }
    }
}
