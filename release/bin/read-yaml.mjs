#!/usr/bin/env node
/**
 * Les YAML-config og skriv JSON til stdout (brukes av release-scripts).
 * Usage: node release/bin/read-yaml.mjs path/to/file.yml
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import yaml from 'js-yaml';

const file = process.argv[2];
if (!file) {
  console.error('Usage: node read-yaml.mjs <file.yml>');
  process.exit(1);
}

const resolved = path.resolve(file);
const content = fs.readFileSync(resolved, 'utf8');
process.stdout.write(JSON.stringify(yaml.load(content)));
