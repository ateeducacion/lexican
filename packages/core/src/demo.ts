/** Fictitious demo accounts (§16). Shown under the login form in the demo build only. */
export const DEMO_ACCOUNTS = [
  { label: 'Profesora', email: 'profesor@ejemplo.com', password: 'profesor', firstName: 'Laura', lastName: 'Méndez', role: 'teacher' },
  { label: 'Alumno 1', email: 'alumno1@ejemplo.com', password: 'alumno1', firstName: 'Daniel', lastName: 'Suárez', role: 'student' },
  { label: 'Alumna 2', email: 'alumno2@ejemplo.com', password: 'alumno2', firstName: 'Aitana', lastName: 'Perera', role: 'student' },
  { label: 'Administración', email: 'admin@ejemplo.com', password: 'admin', firstName: 'Admin', lastName: 'Demo', role: 'admin' },
] as const;
