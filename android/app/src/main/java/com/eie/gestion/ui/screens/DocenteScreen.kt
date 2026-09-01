package com.eie.gestion.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.eie.gestion.data.model.*
import com.eie.gestion.data.repository.AppRepository
import com.eie.gestion.ui.theme.EmiBlue
import com.eie.gestion.ui.theme.EmiYellow
import com.eie.gestion.ui.theme.SuccessGreen
import com.eie.gestion.ui.theme.TextMuted
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import androidx.compose.material3.ExperimentalMaterial3Api

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DocenteScreen(
    userId: Int,
    repository: AppRepository,
    onLogout: () -> Unit
) {
    val coroutineScope = rememberCoroutineScope()
    var paralelos by remember { mutableStateOf<List<Paralelo>>(emptyList()) }
    var selectedParalelo by remember { mutableStateOf<Paralelo?>(null) }
    var isLoading by remember { mutableStateOf(false) }

    // Estados para diálogos
    var selectedInscripcionForNota by remember { mutableStateOf<Inscripcion?>(null) }
    var selectedInscripcionForAsistencia by remember { mutableStateOf<Inscripcion?>(null) }

    fun loadData() {
        isLoading = true
        coroutineScope.launch {
            repository.getMisParalelos(userId).fold(
                onSuccess = { response ->
                    paralelos = response.paralelos
                    // Si ya teníamos uno seleccionado, actualizarlo con los datos frescos
                    if (selectedParalelo != null) {
                        selectedParalelo = response.paralelos.find { it.idParalelo == selectedParalelo!!.idParalelo }
                    }
                },
                onFailure = { /* Manejar error */ }
            )
            isLoading = false
        }
    }

    LaunchedEffect(userId) {
        loadData()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Panel del Docente", color = Color.White, fontWeight = FontWeight.Bold) },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = EmiBlue),
                navigationIcon = {
                    if (selectedParalelo != null) {
                        IconButton(onClick = { selectedParalelo = null }) {
                            Icon(Icons.Default.ArrowBack, contentDescription = "Volver", tint = Color.White)
                        }
                    }
                },
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
            } else if (selectedParalelo == null) {
                // LISTADO DE PARALELOS ASIGNADOS
                if (paralelos.isEmpty()) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text("No tienes paralelos asignados en este período.", color = TextMuted)
                    }
                } else {
                    LazyColumn(
                        contentPadding = PaddingValues(16.dp),
                        verticalArrangement = Arrangement.spacedBy(16.dp)
                    ) {
                        item {
                            Text(
                                text = "Mis Materias y Paralelos",
                                fontSize = 18.sp,
                                fontWeight = FontWeight.Bold,
                                color = EmiBlue,
                                modifier = Modifier.padding(bottom = 8.dp)
                            )
                        }
                        items(paralelos) { paralelo ->
                            Card(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable { selectedParalelo = paralelo },
                                shape = RoundedCornerShape(12.dp),
                                colors = CardDefaults.cardColors(containerColor = Color.White),
                                elevation = CardDefaults.cardElevation(2.dp)
                            ) {
                                Column(modifier = Modifier.padding(16.dp)) {
                                    Text(
                                        text = paralelo.curso?.nombreCurso ?: "Curso",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 16.sp,
                                        color = EmiBlue
                                    )
                                    Spacer(modifier = Modifier.height(4.dp))
                                    Text(
                                        text = "Paralelo: ${paralelo.nombreParalelo}",
                                        fontSize = 14.sp,
                                        fontWeight = FontWeight.Medium
                                    )
                                    paralelo.aula?.let {
                                        Text(
                                            text = "Aula: ${it.nombreAula}",
                                            fontSize = 13.sp,
                                            color = TextMuted
                                        )
                                    }
                                    val count = paralelo.inscripciones?.size ?: 0
                                    Text(
                                        text = "$count estudiante(s) inscrito(s)",
                                        fontSize = 13.sp,
                                        color = EmiYellow,
                                        fontWeight = FontWeight.Bold
                                    )
                                }
                            }
                        }
                    }
                }
            } else {
                // DETALLE DE PARALELO (LISTADO DE ESTUDIANTES)
                val estudiantesInscritos = selectedParalelo!!.inscripciones ?: emptyList()
                if (estudiantesInscritos.isEmpty()) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Text("No hay estudiantes inscritos en este paralelo.", color = TextMuted)
                    }
                } else {
                    LazyColumn(
                        contentPadding = PaddingValues(16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        item {
                            Column(modifier = Modifier.padding(bottom = 12.dp)) {
                                Text(
                                    text = selectedParalelo!!.curso?.nombreCurso ?: "",
                                    fontSize = 18.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = EmiBlue
                                )
                                Text(
                                    text = "Paralelo ${selectedParalelo!!.nombreParalelo} - Estudiantes",
                                    fontSize = 14.sp,
                                    color = TextMuted
                                )
                            }
                        }

                        items(estudiantesInscritos) { inscripcion ->
                            val est = inscripcion.estudiante
                            Card(
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(12.dp),
                                colors = CardDefaults.cardColors(containerColor = Color.White),
                                elevation = CardDefaults.cardElevation(2.dp)
                            ) {
                                Row(
                                    modifier = Modifier
                                        .padding(16.dp)
                                        .fillMaxWidth(),
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Column(modifier = Modifier.weight(1f)) {
                                        Text(
                                            text = est?.let { "${it.nombres} ${it.apellidos}" } ?: "Estudiante #${inscripcion.idEstudiante}",
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 15.sp
                                        )
                                        Text(
                                            text = "CI: ${est?.ci ?: ""}",
                                            fontSize = 13.sp,
                                            color = TextMuted
                                        )
                                        
                                        // Mostrar notas si existen
                                        val notas = inscripcion.flatNotas ?: emptyList()
                                        if (notas.isNotEmpty()) {
                                            Row(modifier = Modifier.padding(top = 4.dp)) {
                                                notas.forEach { nota ->
                                                    Text(
                                                        text = "${nota.periodo?.take(9)}: ${nota.nota}",
                                                        fontSize = 11.sp,
                                                        color = EmiBlue,
                                                        fontWeight = FontWeight.SemiBold,
                                                        modifier = Modifier.padding(end = 8.dp)
                                                    )
                                                }
                                            }
                                        }
                                    }

                                    Row {
                                        IconButton(onClick = { selectedInscripcionForNota = inscripcion }) {
                                            Icon(Icons.Default.Grade, contentDescription = "Notas", tint = EmiYellow)
                                        }
                                        IconButton(onClick = { selectedInscripcionForAsistencia = inscripcion }) {
                                            Icon(Icons.Default.AssignmentTurnedIn, contentDescription = "Asistencia", tint = SuccessGreen)
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // DIÁLOGO DE NOTAS
            if (selectedInscripcionForNota != null) {
                DocenteNotaDialog(
                    inscripcion = selectedInscripcionForNota!!,
                    onDismiss = { selectedInscripcionForNota = null },
                    onSave = { notaVal, periodo, obs ->
                        coroutineScope.launch {
                            repository.saveNota(
                                selectedInscripcionForNota!!.idInscripcion,
                                SaveNotaRequest(notaVal, periodo, obs)
                            ).fold(
                                onSuccess = {
                                    selectedInscripcionForNota = null
                                    loadData()
                                },
                                onFailure = { /* Manejar error */ }
                            )
                        }
                    }
                )
            }

            // DIÁLOGO DE ASISTENCIA
            if (selectedInscripcionForAsistencia != null) {
                DocenteAsistenciaDialog(
                    inscripcion = selectedInscripcionForAsistencia!!,
                    onDismiss = { selectedInscripcionForAsistencia = null },
                    onSave = { fecha, estado, obs ->
                        coroutineScope.launch {
                            repository.saveAsistencia(
                                selectedInscripcionForAsistencia!!.idInscripcion,
                                SaveAsistenciaRequest(fecha, estado, obs)
                            ).fold(
                                onSuccess = {
                                    selectedInscripcionForAsistencia = null
                                    loadData()
                                },
                                onFailure = { /* Manejar error */ }
                            )
                        }
                    }
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DocenteNotaDialog(
    inscripcion: Inscripcion,
    onDismiss: () -> Unit,
    onSave: (Double, String, String?) -> Unit
) {
    var notaStr by remember { mutableStateOf("") }
    var periodo by remember { mutableStateOf("Parcial 1") }
    var observacion by remember { mutableStateOf("") }
    var periodoExpanded by remember { mutableStateOf(false) }
    val periodos = listOf("Parcial 1", "Parcial 2", "Examen Final")

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Text(
                "Registrar Nota - ${inscripcion.estudiante?.nombres ?: ""}",
                fontWeight = FontWeight.Bold,
                color = EmiBlue,
                fontSize = 18.sp
            )
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(16.dp), modifier = Modifier.fillMaxWidth()) {
                
                // Selector Periodo
                Column {
                    Text("Período Evaluativo:", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                    Box {
                        OutlinedTextField(
                            value = periodo,
                            onValueChange = {},
                            readOnly = true,
                            trailingIcon = { Icon(Icons.Default.ArrowDropDown, null) },
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { periodoExpanded = true },
                            enabled = false,
                            colors = OutlinedTextFieldDefaults.colors(
                                disabledTextColor = Color.Black,
                                disabledBorderColor = Color.Gray
                            )
                        )
                        DropdownMenu(
                            expanded = periodoExpanded,
                            onDismissRequest = { periodoExpanded = false },
                            modifier = Modifier.fillMaxWidth(0.6f)
                        ) {
                            periodos.forEach { p ->
                                DropdownMenuItem(
                                    text = { Text(p) },
                                    onClick = {
                                        periodo = p
                                        periodoExpanded = false
                                    }
                                )
                            }
                        }
                    }
                }

                // Input Nota
                OutlinedTextField(
                    value = notaStr,
                    onValueChange = {
                        // Acepta solo números y un punto
                        if (it.isEmpty() || it.toDoubleOrNull() != null) {
                            notaStr = it
                        }
                    },
                    label = { Text("Calificación (0 - 100) *") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth()
                )

                // Observación
                OutlinedTextField(
                    value = observacion,
                    onValueChange = { observacion = it },
                    label = { Text("Observación (Opcional)") },
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    val notaVal = notaStr.toDoubleOrNull()
                    if (notaVal != null && notaVal in 0.0..100.0) {
                        onSave(notaVal, periodo, observacion.ifEmpty { null })
                    }
                },
                colors = ButtonDefaults.buttonColors(containerColor = EmiBlue),
                enabled = notaStr.isNotEmpty() && notaStr.toDoubleOrNull() != null && notaStr.toDouble() in 0.0..100.0
            ) {
                Text("Guardar", color = Color.White)
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancelar", color = EmiBlue)
            }
        }
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DocenteAsistenciaDialog(
    inscripcion: Inscripcion,
    onDismiss: () -> Unit,
    onSave: (String, String, String?) -> Unit
) {
    // Fecha de hoy por defecto
    val defaultFecha = remember { SimpleDateFormat("yyyy-MM-dd", Locale.getDefault()).format(Date()) }
    var fecha by remember { mutableStateOf(defaultFecha) }
    var estado by remember { mutableStateOf("presente") } // presente, ausente, tardanza, justificado
    var observacion by remember { mutableStateOf("") }

    val estados = listOf(
        "presente" to "Presente",
        "ausente" to "Falta / Ausente",
        "tardanza" to "Atraso / Tardanza",
        "justificado" to "Licencia / Justificado"
    )

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Text(
                "Asistencia - ${inscripcion.estudiante?.nombres ?: ""}",
                fontWeight = FontWeight.Bold,
                color = EmiBlue,
                fontSize = 18.sp
            )
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(16.dp), modifier = Modifier.fillMaxWidth()) {
                
                // Input Fecha
                OutlinedTextField(
                    value = fecha,
                    onValueChange = { fecha = it },
                    label = { Text("Fecha (YYYY-MM-DD) *") },
                    modifier = Modifier.fillMaxWidth()
                )

                // Selector de Estado
                Column {
                    Text("Estado de Asistencia:", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                    Spacer(modifier = Modifier.height(8.dp))
                    estados.forEach { (value, label) ->
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { estado = value }
                                .padding(vertical = 4.dp)
                        ) {
                            RadioButton(
                                selected = estado == value,
                                onClick = { estado = value },
                                colors = RadioButtonDefaults.colors(selectedColor = EmiBlue)
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Text(label, fontSize = 14.sp)
                        }
                    }
                }

                // Observación
                OutlinedTextField(
                    value = observacion,
                    onValueChange = { observacion = it },
                    label = { Text("Observación (Opcional)") },
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    if (fecha.isNotEmpty()) {
                        onSave(fecha, estado, observacion.ifEmpty { null })
                    }
                },
                colors = ButtonDefaults.buttonColors(containerColor = EmiBlue),
                enabled = fecha.isNotEmpty()
            ) {
                Text("Registrar", color = Color.White)
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancelar", color = EmiBlue)
            }
        }
    )
}
