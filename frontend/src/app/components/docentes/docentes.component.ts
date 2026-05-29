import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { DocenteService } from '../../services/docente.service';

@Component({
  selector: 'app-docentes',
  standalone: true,
  imports: [ReactiveFormsModule, CommonModule, RouterModule],
  templateUrl: './docentes.component.html',
  styleUrls: ['./docentes.component.css']
})
export class DocentesComponent implements OnInit {
  docenteForm: FormGroup;
  isSubmitting = false;
  successMessage = '';
  errorMessage = '';

  isEditMode = false;
  docenteId: number | null = null;

  constructor(
    private fb: FormBuilder,
    private docenteService: DocenteService,
    private route: ActivatedRoute,
    private router: Router
  ) {
    this.docenteForm = this.fb.group({
      name: ['', [Validators.required, Validators.maxLength(255)]],
      email: ['', [Validators.required, Validators.email, Validators.maxLength(255)]],
      password: ['', [Validators.minLength(8)]], // Requerido solo al crear, opcional al editar
      especialidad: ['', [Validators.maxLength(255)]],
      telefono: ['', [Validators.maxLength(50)]]
    });
  }

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.isEditMode = true;
      this.docenteId = +idParam;

      // La contraseña no es obligatoria al editar
      this.docenteForm.get('password')?.clearValidators();
      this.docenteForm.get('password')?.setValidators([Validators.minLength(8)]);
      this.docenteForm.get('password')?.updateValueAndValidity();

      this.loadDocenteData();
    } else {
      // Obligatoria al crear
      this.docenteForm.get('password')?.setValidators([Validators.required, Validators.minLength(8)]);
      this.docenteForm.get('password')?.updateValueAndValidity();
    }
  }

  loadDocenteData() {
    if (!this.docenteId) return;

    this.docenteService.getDocente(this.docenteId).subscribe({
      next: (res) => {
        this.docenteForm.patchValue({
          name: res.user.name,
          email: res.user.email,
          especialidad: res.docente.especialidad,
          telefono: res.docente.telefono
        });
      },
      error: (err) => {
        this.errorMessage = 'No se pudo cargar la información del docente.';
      }
    });
  }

  onSubmit(): void {
    if (this.docenteForm.invalid) {
      this.docenteForm.markAllAsTouched();
      return;
    }

    this.isSubmitting = true;
    this.successMessage = '';
    this.errorMessage = '';

    if (this.isEditMode && this.docenteId) {
      // Remover contraseña vacía para evitar validación en backend si espera string
      const updateData = { ...this.docenteForm.value };
      if (!updateData.password) {
        delete updateData.password;
      }

      this.docenteService.updateDocente(this.docenteId, updateData).subscribe({
        next: (response) => {
          this.isSubmitting = false;
          this.successMessage = 'Perfil actualizado exitosamente.';
        },
        error: (error) => {
          this.isSubmitting = false;
          this.errorMessage = error.error?.message || 'Error al actualizar el perfil.';
        }
      });
    } else {
      this.docenteService.registerDocente(this.docenteForm.value).subscribe({
        next: (response) => {
          this.isSubmitting = false;
          this.successMessage = 'Instructor registrado exitosamente.';
          this.docenteForm.reset();
        },
        error: (error) => {
          this.isSubmitting = false;
          this.errorMessage = error.error?.message || 'Error al registrar el instructor. Verifica los datos.';
        }
      });
    }
  }
}
