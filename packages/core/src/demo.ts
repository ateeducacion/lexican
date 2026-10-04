/** Fictitious demo accounts (§16). Shown under the login form in the demo build only. */
export const DEMO_ACCOUNTS = [
  {
    label: 'Profesora',
    email: 'profesor@ejemplo.com',
    password: 'profesor',
    firstName: 'Yaiza',
    lastName: 'Tutoriales',
    role: 'teacher',
  },
  {
    label: 'Alumno 1',
    email: 'alumno1@ejemplo.com',
    password: 'alumno1',
    firstName: 'Alumno',
    lastName: 'Padrón Armas',
    role: 'student',
  },
  {
    label: 'Alumna 2',
    email: 'alumno2@ejemplo.com',
    password: 'alumno2',
    firstName: 'Alumna',
    lastName: 'Armas Padrón',
    role: 'student',
  },
  {
    label: 'Administración',
    email: 'admin@ejemplo.com',
    password: 'admin',
    firstName: 'Admin',
    lastName: 'Demo',
    role: 'admin',
  },
] as const;
