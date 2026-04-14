#!/usr/bin/env bash
set -euo pipefail

SERVICE_NAME="cloudbridge-queue.service"
SERVICE_SRC="deploy/systemd/${SERVICE_NAME}"
SERVICE_DEST="/etc/systemd/system/${SERVICE_NAME}"

if [[ ! -f "${SERVICE_SRC}" ]]; then
  echo "Missing ${SERVICE_SRC}"
  exit 1
fi

echo "Copying ${SERVICE_SRC} -> ${SERVICE_DEST}"
sudo cp "${SERVICE_SRC}" "${SERVICE_DEST}"

echo "Reloading systemd"
sudo systemctl daemon-reload

echo "Enabling and starting ${SERVICE_NAME}"
sudo systemctl enable --now cloudbridge-queue

echo
echo "Done. Current status:"
sudo systemctl status cloudbridge-queue --no-pager
