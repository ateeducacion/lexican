import { afterEach, describe, expect, it, vi } from 'vitest';
import { fakeDirectory, httpDirectory, parseCauceResponse } from './cauce.ts';

afterEach(() => vi.unstubAllGlobals());

const response = (usuario: string, centro = '') =>
  `<CheckUsuarioAutorizadoResponse><Usuario><InfoUsuario>${usuario}</InfoUsuario></Usuario>${centro}<MensajeError/></CheckUsuarioAutorizadoResponse>`;

describe('CAUCE response mapping', () => {
  it('keeps unknown entities verbatim, uses the code when a school has no name and tolerates no schools', () => {
    const p = parseCauceResponse(
      's',
      response(
        '<Nombre>Ana &bogus; Mar&iacute;a</Nombre><CorreoElectronico>ana@ejemplo.com</CorreoElectronico>',
        '<Centro><InfoCentro><Codigo>35000001</Codigo><Rol>4</Rol></InfoCentro></Centro>',
      ),
    );
    expect(p).toEqual({
      subject: 's',
      firstName: 'Ana &bogus; María',
      lastName: '',
      email: 'ana@ejemplo.com',
      schools: [{ code: '35000001', name: '35000001', role: '4' }],
    });
    expect(
      parseCauceResponse('s', response('<Apellidos>Solo</Apellidos><Email>no es correo</Email>')),
    ).toMatchObject({
      lastName: 'Solo',
      email: null,
      schools: [],
    });
  });

  it('refuses a profile without any name', () => {
    expect(() => parseCauceResponse('s', response('<Email>a@ejemplo.com</Email>'))).toThrow(
      /no está autorizado/,
    );
  });
});

describe('directory adapters', () => {
  it('the HTTP adapter reports a network failure as unavailable', async () => {
    vi.stubGlobal('fetch', async () => {
      throw new TypeError('fetch failed');
    });
    await expect(
      httpDirectory({ url: 'https://cauce.example.test/', token: 't', timeoutMs: 10 }).lookup('x'),
    ).rejects.toMatchObject({
      code: 'unavailable',
    });
  });

  it('the fake directory returns fixed profiles and refuses unknown subjects', async () => {
    const dir = fakeDirectory({
      profe: { firstName: 'P', lastName: 'F', email: null, schools: [] },
    });
    expect(await dir.lookup('profe')).toEqual({
      subject: 'profe',
      firstName: 'P',
      lastName: 'F',
      email: null,
      schools: [],
    });
    await expect(dir.lookup('otro')).rejects.toMatchObject({ code: 'forbidden' });
  });
});
