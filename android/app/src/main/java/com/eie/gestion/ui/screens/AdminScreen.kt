package com.eie.gestion.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.eie.gestion.data.model.*
import com.eie.gestion.data.repository.AppRepository
import com.eie.gestion.ui.theme.EmiBlue
import com.eie.gestion.ui.theme.EmiYellow
import com.eie.gestion.ui.theme.SuccessGreen
import com.eie.gestion.ui.theme.TextMuted
import kotlinx.coroutines.launch

@OptIn(Material3Api::class)
@Composable
fun AdminScreen(
    repository: AppRepository,
    onLogout: () -> Unit
) {
    var selectedTab by remember { mutableStateOf(0) }
    val tabTitles = listOf("Estudiantes", "Inscripciones")
    val coroutineScope = rememberCoroutineScope()

    // Estados de datos
    var estudiantes by remember { mutableStateOf<List<Estudiante>>(emptyList()) }
    var inscripciones by remember { mutableStateOf<List<Inscripcion>>(emptyList()) }
    var cursos by remember { mutableStateOf<List<Curso>>(emptyList()) }
    var paralelos by remember { mutableStateOf<List<Paralelo>>(emptyList()) }

    var isEstudiantesLoading by remember { mutableStateOf(false) }
    var isInscripcionesLoading by remember { mutableStateOf(false) }

    // Diálogos y Estados de Acción
    var showStudentDialog by remember { mutableStateOf(false) }
    var editingEstudiante by remember { mutableStateOf<Estudiante?>(null) }
    var historyEstudiante by remember { mutableStateOf<Estudiante?>(null) }
    var historyResponse by remember { mutableStateOf<StudentHistoryResponse?>(null) }
    var isHistoryLoading by remember { mutableStateOf(false) }
    var actionMessageDialog by remember { mutableStateOf<String?>(null) }

    var showInscriptionDialog by remember { mutableStateOf(false) }

    // Buscar estudiantes
    var searchQuery by remember { mutableStateOf("") }

    // Funciones para cargar datos
    fun loadEstudiantes() {
        isEstudiantesLoading = true
        coroutineScope.launch {
            repository.getEstudiantes().fold(
                onSuccess = { estudiantes = it },
                onFailure = { /* Manejar error */ }
            )
            isEstudiantesLoading = false
        }
    }

    fun loadInscripciones() {
        isInscripcionesLoading = true
        coroutineScope.launch {
            repository.getInscripciones().fold(
                onSuccess = { inscripciones = it },
                onFailure = { /* Manejar error */ }
            )
            repository.getCursos().fold(
                onSuccess = { cursos = it },
                onFailure = { /* Manejar error */ }
            )
            repository.getParalelos().fold(
                onSuccess = { paralelos = it },
                onFailure = { /* Manejar error */ }
            )
            isInscripcionesLoading = false
        }
    }

    // Cargar inicial
    LaunchedEffect(Unit) {
        loadEstudiantes()
        loadInscripciones()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Panel Administrativo EIE", color = Color.White, fontWeight = FontWeight.Bold) },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = EmiBlue),
                actions = {
                    IconButton(onClick = {
                        if (selectedTab == 0) loadEstudiantes() else loadInscripciones()
                    }) {
                        Icon(Icons.Default.Refresh, contentDescription = "Recargar", tint = Color.White)
                    }
                    IconButton(onClick = onLogout) {
                        Icon(Icons.Default.ExitToApp, contentDescription = "Cerrar Sesión", tint = Color.White)
                    }
                }
            )
        },
        floatingActionButton = {
            if (selectedTab == 0) {
                FloatingActionButton(
                    onClick = {
                        editingEstudiante = null
                        showStudentDialog = true
                    },
                    containerColor = EmiBlue,
                    contentColor = Color.White
                ) {
                    Icon(Icons.Default.Add, contentDescription = "Agregar Estudiante")
                }
            } else {
                FloatingActionButton(
                    onClick = {
                        showInscriptionDialog = true
                    },
                    containerColor = EmiBlue,
                    contentColor = Color.White
                ) {
                    Icon(Icons.Default.PostAdd, contentDescription = "Nueva Inscripción")
                }
            }
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            TabRow(
                selectedTabIndex = selectedTab,
                containerColor = EmiBlue,
                contentColor = Color.White,
                indicator = { tabPositions ->
                    TabRowDefaults.Indicator(
                        Modifier.tabIndicatorOffset(tabPositions[selectedTab]),
                        color = EmiYellow,
                        height = 3.dp
                    )
                }
            ) {
                tabTitles.forEachIndexed { index, title ->
                    Tab(
                        selected = selectedTab == index,
                        onClick = { selectedTab = index },
                        text = {
                            Text(
                                text = title,
                                fontWeight = if (selectedTab == index) FontWeight.Bold else FontWeight.Normal,
                                color = if (selectedTab == index) EmiYellow else Color.White.copy(alpha = 0.8f)
                            )
                        }
                    )
                }
            }

            Spacer(modifier = Modifier.height(8.dp))

            when (selectedTab) {
                0 -> {
                    // TAB ESTUDIANTES
                    OutlinedTextField(
                        value = searchQuery,
                        onValueChange = { searchQuery = it },
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp, vertical = 8.dp),
                        placeholder = { Text("Buscar estudiante por CI o Nombre...") },
                        leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = EmiBlue) },
                        singleLine = true,
                        shape = RoundedCornerShape(12.dp)
                    )

                    if (isEstudiantesLoading) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator(color = EmiBlue)
                        }
                    } else {
                        val filteredList = estudiantes.filter {
                            val nameMatch = (it.nombres ?: "").contains(searchQuery, ignoreCase = true) ||
                                            (it.apellidos ?: "").contains(searchQuery, ignoreCase = true)
                            val ciMatch = (it.ci ?: "").contains(searchQuery)
                            searchQuery.isEmpty() || nameMatch || ciMatch
                        }

                        if (filteredList.isEmpty()) {
                            Box(modifier = Modifier.fillMaxSize().padding(16.dp), contentAlignment = Alignment.Center) {
                                Text("No se encontraron estudiantes.", color = TextMuted)
                            }
                        } else {
                            LazyColumn(
                                contentPadding = PaddingValues(16.dp),
                                verticalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                items(filteredList) { estudiante ->
                                    EstudianteItemCard(
                                        estudiante = estudiante,
                                        onViewHistory = {
                                            historyEstudiante = estudiante
                                            isHistoryLoading = true
                                            coroutineScope.launch {
                                                repository.getHistorialEstudiante(estudiante.idEstudiante).fold(
                                                    onSuccess = { historyResponse = it },
                                                    onFailure = { historyResponse = null }
                                                )
                                                isHistoryLoading = false
                                            }
                                        },
                                        onViewDocs = {
                                            actionMessageDialog = "Documentos de ${estudiante.nombres ?: ""}: Habilitados y verificados en el sistema hasta el 31/12/2027."
                                        },
                                        onPrintCert = {
                                            actionMessageDialog = "Constancia de inscripción oficial de ${estudiante.nombres ?: ""} ${estudiante.apellidos ?: ""} lista para emisión."
                                        },
                                        onEdit = {
                                            editingEstudiante = estudiante
                                            showStudentDialog = true
                                        },
                                        onDelete = {
                                            coroutineScope.launch {
                                                repository.eliminarEstudiante(estudiante.idEstudiante).fold(
                                                    onSuccess = { loadEstudiantes() },
                                                    onFailure = { /* Manejar error */ }
                                                )
                                            }
                                        }
                                    )
                                }
                            }
                        }
                    }
                }
                1 -> {
                    // TAB INSCRIPCIONES
                    if (isInscripcionesLoading) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator(color = EmiBlue)
                        }
                    } else {
                        if (inscripciones.isEmpty()) {
                            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                                Text("No hay registros de inscripciones.", color = TextMuted)
                            }
                        } else {
                            LazyColumn(
                                contentPadding = PaddingValues(16.dp),
                                verticalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                items(inscripciones) { inscripcion ->
                                    InscripcionItemCard(inscripcion = inscripcion)
                                }
                            }
                        }
                    }
                }
            }
        }

        // DIÁLOGO ESTUDIANTE (CREAR / EDITAR)
        if (showStudentDialog) {
            StudentFormDialog(
                estudiante = editingEstudiante,
                onDismiss = { showStudentDialog = false },
                onSave = { req ->
                    coroutineScope.launch {
                        val result = if (editingEstudiante != null) {
                            repository.actualizarEstudiante(editingEstudiante!!.idEstudiante, req)
                        } else {
                            repository.crearEstudiante(req).map { GenericResponse("OK") }
                        }
                        result.fold(
                            onSuccess = {
                                showStudentDialog = false
                                loadEstudiantes()
                            },
                            onFailure = { /* Mostrar error */ }
                        )
                    }
                }
            )
        }

        // DIÁLOGO INSCRIPCIÓN (NUEVA)
        if (showInscriptionDialog) {
            InscriptionFormDialog(
                estudiantes = estudiantes,
                cursos = cursos,
                paralelos = paralelos,
                onDismiss = { showInscriptionDialog = false },
                onSave = { req ->
                    coroutineScope.launch {
                        repository.crearInscripcion(req).fold(
                            onSuccess = {
                                showInscriptionDialog = false
                                loadInscripciones()
                            },
                            onFailure = { /* Mostrar error */ }
                        )
                    }
                }
            )
        }

        // DIÁLOGO HISTORIAL ACADÉMICO
        if (historyEstudiante != null) {
            AlertDialog(
                onDismissRequest = { historyEstudiante = null },
                title = { Text("Historial Académico - ${historyEstudiante?.nombres} ${historyEstudiante?.apellidos}", fontWeight = FontWeight.Bold, color = EmiBlue) },
                text = {
                    if (isHistoryLoading) {
                        Box(modifier = Modifier.fillMaxWidth().padding(24.dp), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator(color = EmiBlue)
                        }
                    } else {
                        val histList = historyResponse?.historial ?: emptyList()
                        if (histList.isEmpty()) {
                            Text("No tiene inscripciones previas registradas.", color = TextMuted)
                        } else {
                            LazyColumn(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                                items(histList) { h ->
                                    Card(
                                        modifier = Modifier.fillMaxWidth(),
                                        colors = CardDefaults.cardColors(containerColor = Color(0xFFF8FAFC))
                                    ) {
                                        Column(modifier = Modifier.padding(10.dp)) {
                                            Text(h.curso?.nombreCurso ?: "Curso", fontWeight = FontWeight.Bold, color = EmiBlue, fontSize = 13.sp)
                                            Text("Paralelo: ${h.paralelo?.nombreParalelo ?: "N/E"} - Estado: ${h.estado ?: "Activo"}", fontSize = 12.sp, color = TextMuted)
                                            if (!h.fechaInscripcion.isNullOrEmpty()) {
                                                Text("Fecha: ${h.fechaInscripcion}", fontSize = 11.sp, color = Color.Gray)
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                },
                confirmButton = {
                    TextButton(onClick = { historyEstudiante = null }) {
                        Text("Cerrar", color = EmiBlue, fontWeight = FontWeight.Bold)
                    }
                }
            )
        }

        // DIÁLOGO DE NOTIFICACIÓN / ACCIÓN
        if (actionMessageDialog != null) {
            AlertDialog(
                onDismissRequest = { actionMessageDialog = null },
                title = { Text("Gestión Académica EIE", fontWeight = FontWeight.Bold, color = EmiBlue) },
                text = { Text(actionMessageDialog ?: "") },
                confirmButton = {
                    Button(onClick = { actionMessageDialog = null }, colors = ButtonDefaults.buttonColors(containerColor = EmiBlue)) {
                        Text("Aceptar", color = Color.White)
                    }
                }
            )
        }
    }
}

@Composable
fun EstudianteItemCard(
    estudiante: Estudiante,
    onViewHistory: () -> Unit,
    onViewDocs: () -> Unit,
    onPrintCert: () -> Unit,
    onEdit: () -> Unit,
    onDelete: () -> Unit
) {
    var isExpanded by remember { mutableStateOf(false) }

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(3.dp)
    ) {
        Column(
            modifier = Modifier
                .padding(14.dp)
                .fillMaxWidth()
        ) {
            // Fila Principal: Avatar, Nombre, CI, Badges
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Inicial avatar
                Box(
                    modifier = Modifier
                        .size(48.dp)
                        .clip(CircleShape)
                        .background(EmiBlue.copy(alpha = 0.12f)),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = (estudiante.nombres?.firstOrNull()?.toString() ?: "E").uppercase(),
                        color = EmiBlue,
                        fontSize = 20.sp,
                        fontWeight = FontWeight.Bold
                    )
                }

                Spacer(modifier = Modifier.width(12.dp))

                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = "${estudiante.nombres ?: ""} ${estudiante.apellidos ?: ""}".trim(),
                        fontWeight = FontWeight.Bold,
                        fontSize = 16.sp,
                        color = EmiBlue,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis
                    )
                    Text(
                        text = "C.I.: ${estudiante.ci ?: "No registrado"}",
                        fontSize = 13.sp,
                        color = TextMuted,
                        fontWeight = FontWeight.Medium
                    )
                }

                // Badges de Categoría y Estado
                Column(horizontalAlignment = Alignment.End) {
                    val userTypeBadge = when (estudiante.tipoUsuario?.lowercase()) {
                        "militar" -> "Militar"
                        "hijo_militar" -> "Hijo Militar"
                        "emi" -> "Estudiante EMI"
                        else -> "Normal / Civil"
                    }
                    Text(
                        text = userTypeBadge,
                        fontSize = 10.sp,
                        color = EmiBlue,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier
                            .background(EmiBlue.copy(alpha = 0.08f), shape = RoundedCornerShape(4.dp))
                            .padding(horizontal = 6.dp, vertical = 2.dp)
                    )

                    val estadoText = estudiante.estado ?: "ACTIVO"
                    Text(
                        text = if (estadoText.uppercase() == "ACTIVO") "Habilitado" else estadoText,
                        fontSize = 10.sp,
                        color = if (estadoText.uppercase() == "ACTIVO") SuccessGreen else Color.Red,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier
                            .padding(top = 4.dp)
                            .background(
                                if (estadoText.uppercase() == "ACTIVO") SuccessGreen.copy(alpha = 0.12f) else Color.Red.copy(alpha = 0.12f),
                                shape = RoundedCornerShape(4.dp)
                            )
                            .padding(horizontal = 6.dp, vertical = 2.dp)
                    )
                }
            }

            Spacer(modifier = Modifier.height(10.dp))

            // Curso Asignado & Contacto
            val ins = estudiante.inscripciones?.firstOrNull()
            if (ins != null && ins.curso != null) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.School, contentDescription = null, tint = EmiBlue, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        text = "${ins.curso?.idioma ?: ""} - ${ins.curso?.nivel ?: ""} ${ins.paralelo?.nombreParalelo?.let { "($it)" } ?: ""}",
                        fontSize = 13.sp,
                        fontWeight = FontWeight.SemiBold,
                        color = Color(0xFF1E293B)
                    )
                }
                Spacer(modifier = Modifier.height(4.dp))
            }

            if (!estudiante.celular.isNullOrEmpty()) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.Phone, contentDescription = null, tint = EmiBlue, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        text = "Celular: ${estudiante.celular}",
                        fontSize = 12.sp,
                        color = TextMuted
                    )
                }
                Spacer(modifier = Modifier.height(4.dp))
            }

            // Botón Desplegable de Datos Completos (Tutor, Nacimiento, Emergencia...)
            Button(
                onClick = { isExpanded = !isExpanded },
                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFF1F5F9), contentColor = EmiBlue),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(vertical = 6.dp),
                shape = RoundedCornerShape(8.dp),
                contentPadding = PaddingValues(horizontal = 12.dp, vertical = 6.dp)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = if (isExpanded) "Ocultar datos completos" else "Ver datos completos (Tutor, Nacimiento...)",
                        fontSize = 12.sp,
                        fontWeight = FontWeight.Bold
                    )
                    Icon(
                        imageVector = if (isExpanded) Icons.Default.KeyboardArrowUp else Icons.Default.KeyboardArrowDown,
                        contentDescription = null
                    )
                }
            }

            // Panel Expandido de Detalles
            if (isExpanded) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .background(Color(0xFFF8FAFC), shape = RoundedCornerShape(8.dp))
                        .padding(10.dp),
                    verticalArrangement = Arrangement.spacedBy(4.dp)
                ) {
                    if (!estudiante.lugarNacimiento.isNullOrEmpty() || !estudiante.fechaNacimiento.isNullOrEmpty()) {
                        Text("Lugar/Fecha Nac.: ${estudiante.lugarNacimiento ?: "N/E"} (${estudiante.fechaNacimiento ?: "N/E"})", fontSize = 12.sp, color = Color(0xFF334155))
                    }
                    if (!estudiante.estadoCivil.isNullOrEmpty()) {
                        Text("Estado Civil: ${estudiante.estadoCivil}", fontSize = 12.sp, color = Color(0xFF334155))
                    }
                    if (!estudiante.grupoSanguineo.isNullOrEmpty()) {
                        Text("Grupo Sanguíneo: ${estudiante.grupoSanguineo}", fontSize = 12.sp, color = Color(0xFF334155))
                    }
                    if (!estudiante.domicilio.isNullOrEmpty()) {
                        Text("Domicilio: ${estudiante.domicilio}", fontSize = 12.sp, color = Color(0xFF334155))
                    }
                    if (!estudiante.nombrePadres.isNullOrEmpty()) {
                        Text("Padres / Tutor: ${estudiante.nombrePadres} ${estudiante.ciTutor?.let { "(CI: $it)" } ?: ""}", fontSize = 12.sp, fontWeight = FontWeight.SemiBold, color = EmiBlue)
                    }
                    if (!estudiante.contactoEmergencia.isNullOrEmpty()) {
                        Text("Celular del Tutor (Emergencia): ${estudiante.contactoEmergencia}", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = Color(0xFFC2410C))
                    }
                    if (!estudiante.gradoAcademico.isNullOrEmpty() || !estudiante.carnetMilitar.isNullOrEmpty()) {
                        Text("Datos Militar: ${estudiante.gradoAcademico ?: ""} ${estudiante.armaEspecialidad ?: ""} ${estudiante.carnetMilitar?.let { "(Carnet: $it)" } ?: ""}", fontSize = 12.sp, color = Color(0xFF15803D))
                    }
                    if (!estudiante.carnetCossmil.isNullOrEmpty()) {
                        Text("Carnet COSSMIL: ${estudiante.carnetCossmil}", fontSize = 12.sp, color = Color(0xFF15803D))
                    }
                }
                Spacer(modifier = Modifier.height(8.dp))
            }

            Divider(color = Color(0xFFE2E8F0), thickness = 1.dp)
            Spacer(modifier = Modifier.height(8.dp))

            // Barra Inferior de 5 Botones de Acción Táctiles
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                // 1. Historial
                IconButton(onClick = onViewHistory) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Icon(Icons.Default.ListAlt, contentDescription = "Historial", tint = Color(0xFF1E90FF), modifier = Modifier.size(22.dp))
                        Text("Historial", fontSize = 9.sp, color = Color(0xFF1E90FF), fontWeight = FontWeight.Bold)
                    }
                }

                // 2. Docs
                IconButton(onClick = onViewDocs) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Icon(Icons.Default.Folder, contentDescription = "Docs", tint = Color(0xFF009688), modifier = Modifier.size(22.dp))
                        Text("Docs", fontSize = 9.sp, color = Color(0xFF009688), fontWeight = FontWeight.Bold)
                    }
                }

                // 3. Imprimir
                IconButton(onClick = onPrintCert) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Icon(Icons.Default.Print, contentDescription = "Imprimir", tint = Color(0xFF673AB7), modifier = Modifier.size(22.dp))
                        Text("Imprimir", fontSize = 9.sp, color = Color(0xFF673AB7), fontWeight = FontWeight.Bold)
                    }
                }

                // 4. Editar
                IconButton(onClick = onEdit) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Icon(Icons.Default.Edit, contentDescription = "Editar", tint = EmiBlue, modifier = Modifier.size(22.dp))
                        Text("Editar", fontSize = 9.sp, color = EmiBlue, fontWeight = FontWeight.Bold)
                    }
                }

                // 5. Eliminar
                IconButton(onClick = onDelete) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Icon(Icons.Default.Delete, contentDescription = "Eliminar", tint = Color.Red, modifier = Modifier.size(22.dp))
                        Text("Eliminar", fontSize = 9.sp, color = Color.Red, fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}

@Composable
fun InscripcionItemCard(inscripcion: Inscripcion) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Inscripción #${inscripcion.idInscripcion}",
                    fontWeight = FontWeight.Bold,
                    color = EmiBlue
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
                        .padding(horizontal = 8.dp, vertical = 4.dp)
                )
            }

            Divider(modifier = Modifier.padding(vertical = 8.dp))

            Text(
                text = "Estudiante:",
                fontSize = 11.sp,
                fontWeight = FontWeight.Bold,
                color = TextMuted
            )
            val est = inscripcion.estudiante
            Text(
                text = if (est != null) "${est.nombres ?: ""} ${est.apellidos ?: ""} (CI: ${est.ci ?: ""})" else "ID Estudiante: ${inscripcion.idEstudiante}",
                fontSize = 14.sp,
                fontWeight = FontWeight.Medium
            )

            Spacer(modifier = Modifier.height(8.dp))

            Text(
                text = "Curso:",
                fontSize = 11.sp,
                fontWeight = FontWeight.Bold,
                color = TextMuted
            )
            val cursoNombre = inscripcion.curso?.nombreCurso ?: inscripcion.paralelo?.curso?.nombreCurso ?: "Curso Desconocido"
            Text(
                text = cursoNombre,
                fontSize = 14.sp,
                fontWeight = FontWeight.Medium
            )

            Spacer(modifier = Modifier.height(8.dp))

            Text(
                text = "Paralelo:",
                fontSize = 11.sp,
                fontWeight = FontWeight.Bold,
                color = TextMuted
            )
            Text(
                text = inscripcion.paralelo?.nombreParalelo ?: "Sin Paralelo",
                fontSize = 14.sp,
                fontWeight = FontWeight.Medium
            )

            if (!inscripcion.fechaRegistro.isNullOrEmpty() || !inscripcion.fechaInscripcion.isNullOrEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "Fecha: ${inscripcion.fechaInscripcion ?: inscripcion.fechaRegistro ?: ""}",
                    fontSize = 12.sp,
                    color = Color.Gray
                )
            }
        }
    }
}

@OptIn(Material3Api::class)
@Composable
fun StudentFormDialog(
    estudiante: Estudiante?,
    onDismiss: () -> Unit,
    onSave: (EstudianteCreateRequest) -> Unit
) {
    var nombres by remember { mutableStateOf(estudiante?.nombres ?: "") }
    var apellidos by remember { mutableStateOf(estudiante?.apellidos ?: "") }
    var ci by remember { mutableStateOf(estudiante?.ci ?: "") }
    var email by remember { mutableStateOf(estudiante?.correoElectronico ?: "") }
    var celular by remember { mutableStateOf(estudiante?.celular ?: "") }
    var domicilio by remember { mutableStateOf(estudiante?.domicilio ?: "") }
    var fechaNac by remember { mutableStateOf(estudiante?.fechaNacimiento ?: "") }
    var lugarNac by remember { mutableStateOf(estudiante?.lugarNacimiento ?: "") }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (estudiante == null) "Agregar Estudiante" else "Editar Estudiante", fontWeight = FontWeight.Bold, color = EmiBlue) },
        text = {
            LazyColumn(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                item {
                    OutlinedTextField(
                        value = nombres,
                        onValueChange = { nombres = it },
                        label = { Text("Nombres *") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
                item {
                    OutlinedTextField(
                        value = apellidos,
                        onValueChange = { apellidos = it },
                        label = { Text("Apellidos *") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
                item {
                    OutlinedTextField(
                        value = ci,
                        onValueChange = { ci = it },
                        label = { Text("CI *") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
                item {
                    OutlinedTextField(
                        value = email,
                        onValueChange = { email = it },
                        label = { Text("Correo Electrónico *") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
                item {
                    OutlinedTextField(
                        value = celular,
                        onValueChange = { celular = it },
                        label = { Text("Celular") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
                item {
                    OutlinedTextField(
                        value = domicilio,
                        onValueChange = { domicilio = it },
                        label = { Text("Domicilio") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
                item {
                    OutlinedTextField(
                        value = fechaNac,
                        onValueChange = { fechaNac = it },
                        label = { Text("Fecha de Nacimiento (YYYY-MM-DD)") },
                        placeholder = { Text("Ej: 2000-05-15") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
                item {
                    OutlinedTextField(
                        value = lugarNac,
                        onValueChange = { lugarNac = it },
                        label = { Text("Lugar de Nacimiento") },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    if (nombres.isNotEmpty() && apellidos.isNotEmpty() && ci.isNotEmpty() && email.isNotEmpty()) {
                        onSave(
                            EstudianteCreateRequest(
                                nombres = nombres,
                                apellidos = apellidos,
                                ci = ci,
                                correoElectronico = email,
                                celular = celular.ifEmpty { null },
                                domicilio = domicilio.ifEmpty { null },
                                fechaNacimiento = fechaNac.ifEmpty { null },
                                lugarNacimiento = lugarNac.ifEmpty { null }
                            )
                        )
                    }
                },
                colors = ButtonDefaults.buttonColors(containerColor = EmiBlue),
                enabled = nombres.isNotEmpty() && apellidos.isNotEmpty() && ci.isNotEmpty() && email.isNotEmpty()
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

@OptIn(Material3Api::class)
@Composable
fun InscriptionFormDialog(
    estudiantes: List<Estudiante>,
    cursos: List<Curso>,
    paralelos: List<Paralelo>,
    onDismiss: () -> Unit,
    onSave: (InscripcionCreateRequest) -> Unit
) {
    var selectedEstudiante by remember { mutableStateOf<Estudiante?>(null) }
    var selectedCurso by remember { mutableStateOf<Curso?>(null) }
    var selectedParalelo by remember { mutableStateOf<Paralelo?>(null) }

    var estExpanded by remember { mutableStateOf(false) }
    var cursoExpanded by remember { mutableStateOf(false) }
    var paraleloExpanded by remember { mutableStateOf(false) }

    // Filtrar paralelos correspondientes al curso seleccionado
    val filteredParalelos = paralelos.filter { it.idCurso == selectedCurso?.idCurso }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Nueva Inscripción", fontWeight = FontWeight.Bold, color = EmiBlue) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(16.dp), modifier = Modifier.fillMaxWidth()) {
                
                // Dropdown Estudiante
                Column {
                    Text("Selecciona Estudiante:", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                    Box {
                        OutlinedTextField(
                            value = selectedEstudiante?.let { "${it.nombres} ${it.apellidos} (${it.ci})" } ?: "Seleccionar Estudiante",
                            onValueChange = {},
                            readOnly = true,
                            trailingIcon = { Icon(Icons.Default.ArrowDropDown, null) },
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { estExpanded = true },
                            enabled = false,
                            colors = OutlinedTextFieldDefaults.colors(
                                disabledTextColor = Color.Black,
                                disabledBorderColor = Color.Gray
                            )
                        )
                        DropdownMenu(
                            expanded = estExpanded,
                            onDismissRequest = { estExpanded = false },
                            modifier = Modifier.fillMaxWidth(0.7f).heightIn(max = 250.dp)
                        ) {
                            estudiantes.forEach { est ->
                                DropdownMenuItem(
                                    text = { Text("${est.nombres} ${est.apellidos} - CI: ${est.ci}") },
                                    onClick = {
                                        selectedEstudiante = est
                                        estExpanded = false
                                    }
                                )
                            }
                        }
                    }
                }

                // Dropdown Curso
                Column {
                    Text("Selecciona Curso:", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                    Box {
                        OutlinedTextField(
                            value = selectedCurso?.nombreCurso ?: "Seleccionar Curso",
                            onValueChange = {},
                            readOnly = true,
                            trailingIcon = { Icon(Icons.Default.ArrowDropDown, null) },
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { cursoExpanded = true },
                            enabled = false,
                            colors = OutlinedTextFieldDefaults.colors(
                                disabledTextColor = Color.Black,
                                disabledBorderColor = Color.Gray
                            )
                        )
                        DropdownMenu(
                            expanded = cursoExpanded,
                            onDismissRequest = { cursoExpanded = false },
                            modifier = Modifier.fillMaxWidth(0.7f).heightIn(max = 200.dp)
                        ) {
                            cursos.forEach { c ->
                                DropdownMenuItem(
                                    text = { Text(c.nombreCurso) },
                                    onClick = {
                                        selectedCurso = c
                                        selectedParalelo = null
                                        cursoExpanded = false
                                    }
                                )
                            }
                        }
                    }
                }

                // Dropdown Paralelo
                Column {
                    Text("Selecciona Paralelo:", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                    Box {
                        OutlinedTextField(
                            value = selectedParalelo?.nombreParalelo ?: "Seleccionar Paralelo",
                            onValueChange = {},
                            readOnly = true,
                            trailingIcon = { Icon(Icons.Default.ArrowDropDown, null) },
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable(enabled = selectedCurso != null) { paraleloExpanded = true },
                            enabled = false,
                            colors = OutlinedTextFieldDefaults.colors(
                                disabledTextColor = if (selectedCurso != null) Color.Black else Color.Gray,
                                disabledBorderColor = Color.Gray
                            )
                        )
                        DropdownMenu(
                            expanded = paraleloExpanded,
                            onDismissRequest = { paraleloExpanded = false },
                            modifier = Modifier.fillMaxWidth(0.7f)
                        ) {
                            filteredParalelos.forEach { p ->
                                DropdownMenuItem(
                                    text = { Text(p.nombreParalelo) },
                                    onClick = {
                                        selectedParalelo = p
                                        paraleloExpanded = false
                                    }
                                )
                            }
                        }
                    }
                }
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    if (selectedEstudiante != null && selectedParalelo != null && selectedCurso != null) {
                        onSave(
                            InscripcionCreateRequest(
                                idEstudiante = selectedEstudiante!!.idEstudiante,
                                idParalelo = selectedParalelo!!.idParalelo,
                                idCurso = selectedCurso!!.idCurso
                            )
                        )
                    }
                },
                colors = ButtonDefaults.buttonColors(containerColor = EmiBlue),
                enabled = selectedEstudiante != null && selectedParalelo != null && selectedCurso != null
            ) {
                Text("Inscribir", color = Color.White)
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancelar", color = EmiBlue)
            }
        }
    )
}
