package com.eie.gestion.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ExitToApp
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.eie.gestion.data.model.EstudianteProfile
import com.eie.gestion.data.model.Inscripcion
import com.eie.gestion.data.repository.AppRepository
import com.eie.gestion.ui.theme.EmiBlue
import com.eie.gestion.ui.theme.EmiYellow
import com.eie.gestion.ui.theme.SuccessGreen
import com.eie.gestion.ui.theme.TextMuted
import kotlinx.coroutines.launch

import androidx.compose.material3.ExperimentalMaterial3Api

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun StudentScreen(
    estudianteId: Int,
    repository: AppRepository,
    onLogout: () -> Unit
) {
    val coroutineScope = rememberCoroutineScope()
    var profile by remember { mutableStateOf<EstudianteProfile?>(null) }
    var historial by remember { mutableStateOf<List<Inscripcion>>(emptyList()) }
    var isLoading by remember { mutableStateOf(false) }

    fun loadData() {
        isLoading = true
        coroutineScope.launch {
            repository.getHistorialEstudiante(estudianteId).fold(
                onSuccess = { response ->
                    profile = response.estudiante
                    historial = response.historial
                },
                onFailure = { /* Manejar error */ }
            )
            isLoading = false
        }
    }

    LaunchedEffect(estudianteId) {
        loadData()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Panel del Estudiante", color = Color.White, fontWeight = FontWeight.Bold) },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = EmiBlue),
                actions = {
                    IconButton(onClick = { loadData() }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Recargar", tint = Color.White)
                    }
                    IconButton(onClick = onLogout) {
                        Icon(Icons.Default.ExitToApp, contentDescription = "Cerrar Sesión", tint = Color.White)
                    }
                }
            )
        }
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            if (isLoading) {
                CircularProgressIndicator(color = EmiBlue, modifier = Modifier.align(Alignment.Center))
            } else {
                LazyColumn(
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    // SECCIÓN 1: PERFIL
                    item {
                        profile?.let { prof ->
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(16.dp),
                                colors = CardDefaults.cardColors(containerColor = Color.White),
                                elevation = CardDefaults.cardElevation(2.dp)
                            ) {
                                Row(
                                    modifier = Modifier.padding(16.dp),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Box(
                                        modifier = Modifier
                                            .size(56.dp)
                                            .clip(CircleShape)
                                            .background(EmiBlue.copy(alpha = 0.1f)),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        Text(
                                            text = (prof.nombres?.firstOrNull()?.toString() ?: "E"),
                                            color = EmiBlue,
                                            fontSize = 24.sp,
                                            fontWeight = FontWeight.Bold
                                        )
                                    }

                                    Spacer(modifier = Modifier.width(16.dp))

                                    Column {
                                        Text(
                                            text = "${prof.nombres ?: ""} ${prof.apellidos ?: ""}",
                                            fontSize = 18.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = EmiBlue
                                        )
                                        Text(
                                            text = "CI: ${prof.ci ?: ""}",
                                            fontSize = 14.sp,
                                            color = TextMuted
                                        )
                                    }
                                }
                            }
                        }
                    }

                    // SECCIÓN 2: HISTORIAL ACADÉMICO / CURSOS
                    item {
                        Text(
                            text = "Mi Historial Académico",
                            fontSize = 16.sp,
                            fontWeight = FontWeight.Bold,
                            color = EmiBlue,
                            modifier = Modifier.padding(top = 8.dp)
                        )
                    }

                    if (historial.isEmpty()) {
                        item {
                            Box(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(vertical = 32.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                Text("No posees inscripciones registradas actualmente.", color = TextMuted)
                            }
                        }
                    } else {
                        items(historial) { inscripcion ->
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(12.dp),
                                colors = CardDefaults.cardColors(containerColor = Color.White),
                                elevation = CardDefaults.cardElevation(2.dp)
                            ) {
                                Column(modifier = Modifier.padding(16.dp)) {
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.SpaceBetween
                                    ) {
                                        Text(
                                            text = inscripcion.curso?.nombreCurso ?: "Materia",
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 15.sp,
                                            color = EmiBlue,
                                            modifier = Modifier.weight(1f)
                                        )
                                        Text(
                                            text = inscripcion.estado?.uppercase() ?: "ACTIVO",
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 12.sp,
                                            color = if (inscripcion.estado?.lowercase() == "inscrito" || inscripcion.estado?.lowercase() == "activo") SuccessGreen else Color.Red,
                                            modifier = Modifier
                                                .background(
                                                    if (inscripcion.estado?.lowercase() == "inscrito" || inscripcion.estado?.lowercase() == "activo") SuccessGreen.copy(alpha = 0.1f) else Color.Red.copy(alpha = 0.1f),
                                                    shape = RoundedCornerShape(4.dp)
                                                )
                                                .padding(horizontal = 6.dp, vertical = 2.dp)
                                        )
                                    }

                                    Text(
                                        text = "Paralelo: ${inscripcion.paralelo?.nombreParalelo ?: "Sin asignar"}",
                                        fontSize = 13.sp,
                                        color = TextMuted,
                                        modifier = Modifier.padding(top = 2.dp)
                                    )

                                    Divider(modifier = Modifier.padding(vertical = 12.dp))

                                    // Mostrar calificaciones
                                    Text(
                                        text = "Calificaciones:",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 13.sp,
                                        color = EmiBlue
                                    )

                                    val notas = inscripcion.flatNotas ?: emptyList()
                                    if (notas.isEmpty()) {
                                        Text(
                                            text = "Aún no se han registrado notas.",
                                            fontSize = 13.sp,
                                            color = Color.Gray,
                                            modifier = Modifier.padding(top = 4.dp)
                                        )
                                    } else {
                                        Column(modifier = Modifier.padding(top = 4.dp)) {
                                            notas.forEach { nota ->
                                                Row(
                                                    modifier = Modifier
                                                        .fillMaxWidth()
                                                        .padding(vertical = 2.dp),
                                                    horizontalArrangement = Arrangement.SpaceBetween
                                                ) {
                                                    Text(text = nota.periodo ?: "", fontSize = 13.sp)
                                                    Text(
                                                        text = "${nota.nota} / 100",
                                                        fontWeight = FontWeight.Bold,
                                                        fontSize = 13.sp,
                                                        color = if ((nota.nota ?: 0.0) >= 51.0) SuccessGreen else Color.Red
                                                    )
                                                }
                                                if (!nota.observacion.isNullOrEmpty()) {
                                                    Text(
                                                        text = "Obs: ${nota.observacion}",
                                                        fontSize = 11.sp,
                                                        color = Color.Gray,
                                                        modifier = Modifier.padding(bottom = 4.dp)
                                                    )
                                                }
                                            }
                                        }
                                    }

                                    // Resumen de asistencias
                                    val asistencias = inscripcion.asistencias ?: emptyList()
                                    if (asistencias.isNotEmpty()) {
                                        Divider(modifier = Modifier.padding(vertical = 12.dp))
                                        Text(
                                            text = "Resumen de Asistencias:",
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 13.sp,
                                            color = EmiBlue
                                        )
                                        
                                        val presentes = asistencias.count { it.estado.lowercase() == "presente" }
                                        val faltas = asistencias.count { it.estado.lowercase() == "ausente" }
                                        val tardanzas = asistencias.count { it.estado.lowercase() == "tardanza" }
                                        val licencias = asistencias.count { it.estado.lowercase() == "justificado" }
                                        
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(top = 6.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Text(text = "Presencias: $presentes", fontSize = 12.sp, color = SuccessGreen, fontWeight = FontWeight.Medium)
                                            Text(text = "Faltas: $faltas", fontSize = 12.sp, color = Color.Red, fontWeight = FontWeight.Medium)
                                            Text(text = "Atrasos: $tardanzas", fontSize = 12.sp, color = EmiYellow, fontWeight = FontWeight.Medium)
                                            Text(text = "Licencias: $licencias", fontSize = 12.sp, color = Color.Gray, fontWeight = FontWeight.Medium)
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
