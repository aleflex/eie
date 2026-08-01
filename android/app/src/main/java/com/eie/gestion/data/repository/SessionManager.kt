package com.eie.gestion.data.repository

import android.content.Context
import android.content.SharedPreferences

class SessionManager(context: Context) {
    private val prefs: SharedPreferences = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)

    companion object {
        private const val PREF_NAME = "EieSession"
        private const val KEY_TOKEN = "auth_token"
        private const val KEY_ROLE = "user_role"
        private const val KEY_EMAIL = "user_email"
        private const val KEY_NAME = "user_name"
        private const val KEY_USER_ID = "user_id"
        private const val KEY_DOCENTE_ID = "docente_id"
        private const val KEY_ESTUDIANTE_ID = "estudiante_id"
        private const val KEY_API_URL = "api_url"
        
        // Host especial de Android para apuntar al localhost del PC que hospeda el emulador
        private const val DEFAULT_API_URL = "http://10.0.2.2:8000/"
    }

    fun saveAuthToken(token: String) {
        prefs.edit().putString(KEY_TOKEN, token).apply()
    }

    fun fetchAuthToken(): String? {
        return prefs.getString(KEY_TOKEN, null)
    }

    fun saveUserRole(role: String) {
        prefs.edit().putString(KEY_ROLE, role).apply()
    }

    fun fetchUserRole(): String? {
        return prefs.getString(KEY_ROLE, null)
    }

    fun saveUserEmail(email: String) {
        prefs.edit().putString(KEY_EMAIL, email).apply()
    }

    fun fetchUserEmail(): String? {
        return prefs.getString(KEY_EMAIL, "")
    }

    fun saveUserName(name: String) {
        prefs.edit().putString(KEY_NAME, name).apply()
    }

    fun fetchUserName(): String? {
        return prefs.getString(KEY_NAME, "")
    }

    fun saveUserId(id: Int) {
        prefs.edit().putInt(KEY_USER_ID, id).apply()
    }

    fun fetchUserId(): Int {
        return prefs.getInt(KEY_USER_ID, -1)
    }

    fun saveDocenteId(id: Int) {
        prefs.edit().putInt(KEY_DOCENTE_ID, id).apply()
    }

    fun fetchDocenteId(): Int {
        return prefs.getInt(KEY_DOCENTE_ID, -1)
    }

    fun saveEstudianteId(id: Int) {
        prefs.edit().putInt(KEY_ESTUDIANTE_ID, id).apply()
    }

    fun fetchEstudianteId(): Int {
        return prefs.getInt(KEY_ESTUDIANTE_ID, -1)
    }

    fun saveApiUrl(url: String) {
        var cleanUrl = url.trim()
        if (!cleanUrl.endsWith("/")) {
            cleanUrl += "/"
        }
        prefs.edit().putString(KEY_API_URL, cleanUrl).apply()
    }

    fun fetchApiUrl(): String {
        return prefs.getString(KEY_API_URL, DEFAULT_API_URL) ?: DEFAULT_API_URL
    }

    fun clearSession() {
        prefs.edit().apply {
            remove(KEY_TOKEN)
            remove(KEY_ROLE)
            remove(KEY_EMAIL)
            remove(KEY_NAME)
            remove(KEY_USER_ID)
            remove(KEY_DOCENTE_ID)
            remove(KEY_ESTUDIANTE_ID)
        }.apply()
    }
}
