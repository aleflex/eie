import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ReportService {
  private apiUrl = `${environment.apiUrl}/api/reports`;

  constructor(private http: HttpClient) {}

  private buildParams(filters?: any): HttpParams {
    let params = new HttpParams();
    if (!filters) return params;

    Object.keys(filters).forEach(key => {
      if (filters[key] !== null && filters[key] !== undefined && filters[key] !== '') {
        params = params.set(key, filters[key]);
      }
    });

    return params;
  }

  /**
   * RF 18 - HU 18: Estadísticas por idioma
   */
  getLanguageStatistics(filters?: any): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/statistics-by-language`, {
      params: this.buildParams(filters)
    });
  }

  /**
   * RF 19 - HU 19: Porcentaje de ocupación de aulas
   */
  getClassroomOccupancy(filters?: any): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/classroom-occupancy`, {
      params: this.buildParams(filters)
    });
  }

  /**
   * Resumen general para KPIs del Dashboard
   */
  getDashboardSummary(filters?: any): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/dashboard-summary`, {
      params: this.buildParams(filters)
    });
  }

  /**
   * RF 20 - HU 20: Descarga en formato Excel / CSV (Blob)
   */
  downloadExcel(filters?: any): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/export/excel`, {
      params: this.buildParams(filters),
      responseType: 'blob'
    });
  }

  /**
   * RF 20 - HU 20: Descarga en formato PDF Oficial (Blob)
   */
  downloadPdf(filters?: any): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/export/pdf`, {
      params: this.buildParams(filters),
      responseType: 'blob'
    });
  }
}
