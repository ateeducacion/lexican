// SPDX 2.3 JSON SBOM of the production dependencies (what the API image and the web bundles ship), from Bun's
// resolution of bun.lock. Bun has no SBOM command; this keeps the release artifact.
// Usage: bun run sbom  (writes sbom.spdx.json)
import { readFileSync, writeFileSync } from 'node:fs';
import { packages } from './deps.mjs';

const root = JSON.parse(readFileSync('package.json', 'utf8'));
const id = (p) => `SPDXRef-Package-${`${p.name}-${p.version}`.replace(/[^A-Za-z0-9.-]/g, '-')}`;
const deps = packages({ prod: true });
const doc = {
  spdxVersion: 'SPDX-2.3',
  dataLicense: 'CC0-1.0',
  SPDXID: 'SPDXRef-DOCUMENT',
  name: `${root.name}@${root.version}`,
  documentNamespace: `https://github.com/ateeducacion/lexican/sbom/${root.version}/${crypto.randomUUID()}`,
  creationInfo: {
    created: new Date().toISOString().replace(/\.\d+Z$/, 'Z'),
    creators: ['Tool: lexican scripts/sbom.mjs'],
  },
  packages: [
    {
      SPDXID: 'SPDXRef-Package-root',
      name: root.name,
      versionInfo: root.version,
      downloadLocation: 'https://github.com/ateeducacion/lexican',
      licenseConcluded: root.license,
      licenseDeclared: root.license,
      filesAnalyzed: false,
    },
    ...deps.map((p) => ({
      SPDXID: id(p),
      name: p.name,
      versionInfo: p.version,
      downloadLocation: `https://registry.npmjs.org/${p.name}/-/${p.name.split('/').pop()}-${p.version}.tgz`,
      licenseConcluded: 'NOASSERTION',
      licenseDeclared: p.license ?? 'NOASSERTION',
      externalRefs: [
        {
          referenceCategory: 'PACKAGE-MANAGER',
          referenceType: 'purl',
          referenceLocator: `pkg:npm/${p.name.replace('@', '%40')}@${p.version}`,
        },
      ],
      filesAnalyzed: false,
    })),
  ],
  relationships: [
    {
      spdxElementId: 'SPDXRef-DOCUMENT',
      relationshipType: 'DESCRIBES',
      relatedSpdxElement: 'SPDXRef-Package-root',
    },
    ...deps.map((p) => ({
      spdxElementId: 'SPDXRef-Package-root',
      relationshipType: 'DEPENDS_ON',
      relatedSpdxElement: id(p),
    })),
  ],
};
writeFileSync('sbom.spdx.json', `${JSON.stringify(doc, null, 2)}\n`);
console.log(`sbom.spdx.json: ${deps.length} production packages`);
