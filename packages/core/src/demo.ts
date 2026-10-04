/** Fictitious school of the demo dataset and of the test CAS profiles. */
export const DEMO_SCHOOL = { code: '99999999', name: 'IES Ficticio Las Palmeras' } as const;

/** Fictitious demo accounts (§16). Shown under the login form in the demo build only. */
export const DEMO_ACCOUNTS = [
  {
    label: 'Profesora',
    email: 'profesor@ejemplo.com',
    password: 'profesor',
    firstName: 'Yaiza',
    lastName: 'Tutoriales',
    role: 'teacher',
    /** Account of the public test CAS (casserverpac4j.dev) mapped to this persona. */
    casSubject: 'alice',
  },
  {
    label: 'Alumno 1',
    email: 'alumno1@ejemplo.com',
    password: 'alumno1',
    firstName: 'Alumno',
    lastName: 'Padrón Armas',
    role: 'student',
    casSubject: 'bob',
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
