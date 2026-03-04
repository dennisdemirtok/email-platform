#!/bin/bash
# Deploy script for Cloudways
# Run this on the server after git pull to sync static assets
# Usage: bash deploy.sh

BASEDIR="$(cd "$(dirname "$0")" && pwd)"

echo "Deploying from: $BASEDIR"

# Copy static assets from public/ to root (needed because Cloudways
# web root is public_html/, not public_html/public/)
echo "Syncing static assets..."
cp -r "$BASEDIR/public/css" "$BASEDIR/"
cp -r "$BASEDIR/public/js" "$BASEDIR/"
cp -f "$BASEDIR/public/favicon.ico" "$BASEDIR/" 2>/dev/null
cp -f "$BASEDIR/public/favicon.png" "$BASEDIR/" 2>/dev/null
cp -f "$BASEDIR/public/robots.txt" "$BASEDIR/" 2>/dev/null

# Ensure writable directory exists and has correct permissions
mkdir -p "$BASEDIR/writable/cache" "$BASEDIR/writable/logs" "$BASEDIR/writable/session" "$BASEDIR/writable/uploads"

echo "Deploy complete!"
