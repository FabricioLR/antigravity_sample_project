#!/bin/bash
set -e

# Define directories that need write access
STORAGE_DIR="storage"

echo "Setting permissions for $STORAGE_DIR..."
# Attempt to set ownership/permissions. This may require sudo or running inside docker
# Instead, we will configure docker to run initialization with correct permissions
chmod -R 777 $STORAGE_DIR || true
mkdir -p $STORAGE_DIR || true

echo "Applying permissions done."
