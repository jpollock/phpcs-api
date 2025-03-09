#!/usr/bin/env node

/**
 * OpenAPI Specification Validator
 * 
 * This script validates the OpenAPI specification file to ensure it follows
 * the OpenAPI 3.0 standards.
 * 
 * Usage:
 *   node bin/validate-openapi.js
 */

const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');

// Check if npm packages are installed
const requiredPackages = ['@apidevtools/swagger-parser'];
let missingPackages = [];

for (const pkg of requiredPackages) {
  try {
    require.resolve(pkg);
  } catch (e) {
    missingPackages.push(pkg);
  }
}

if (missingPackages.length > 0) {
  console.log('Installing required packages...');
  exec(`npm install --no-save ${missingPackages.join(' ')}`, (error, stdout, stderr) => {
    if (error) {
      console.error(`Error installing packages: ${error.message}`);
      return;
    }
    validateOpenAPI();
  });
} else {
  validateOpenAPI();
}

function validateOpenAPI() {
  const SwaggerParser = require('@apidevtools/swagger-parser');
  const specPath = path.resolve(__dirname, '../openapi.yml');

  console.log(`Validating OpenAPI specification at ${specPath}...`);

  SwaggerParser.validate(specPath)
    .then(() => {
      console.log('✅ OpenAPI specification is valid!');
    })
    .catch((err) => {
      console.error('❌ OpenAPI specification validation failed:');
      console.error(err.message);
      if (err.details) {
        console.error(JSON.stringify(err.details, null, 2));
      }
      process.exit(1);
    });
}
