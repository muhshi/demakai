"""
Upload merged SE2026 cleaning dataset CSV to Google Drive as Google Spreadsheet.
Target folder: 1mPNovNWjyDYIjBGO0i-TEuifcKqxJi6a (KBLI)
Credentials: ~/.clasprc.json
"""

import os
import json
import logging
from pathlib import Path

from google.oauth2.credentials import Credentials
from google.auth.transport.requests import Request
from googleapiclient.discovery import build
from googleapiclient.http import MediaFileUpload

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")

TARGET_FOLDER_ID = "1mPNovNWjyDYIjBGO0i-TEuifcKqxJi6a"
SPREADSHEET_TITLE = "PINTAR_KBLI_SE2026_VERIFIKASI_DEMAK"
CSV_FILE_PATH = Path("storage/app/se2026_resolved/merged_se2026_cleaning_dataset.csv")
METADATA_OUTPUT_PATH = Path("storage/app/se2026_resolved/spreadsheet_metadata.json")


def get_drive_credentials():
    clasprc_path = Path.home() / ".clasprc.json"
    if not clasprc_path.exists():
        raise FileNotFoundError(f"clasprc credentials file not found at {clasprc_path}")

    with open(clasprc_path, "r", encoding="utf-8") as f:
        clasp_data = json.load(f)

    tokens = clasp_data.get("tokens", {}).get("default", {})
    client_id = tokens.get("client_id")
    client_secret = tokens.get("client_secret")
    refresh_token = tokens.get("refresh_token")
    access_token = tokens.get("access_token")

    if not refresh_token:
        raise ValueError("refresh_token not found in ~/.clasprc.json")

    creds = Credentials(
        token=access_token,
        refresh_token=refresh_token,
        token_uri="https://oauth2.googleapis.com/token",
        client_id=client_id,
        client_secret=client_secret,
    )

    if creds.expired or not creds.valid:
        logging.info("Refreshing expired Google OAuth credentials...")
        req = Request()
        creds.refresh(req)
        # Update clasp file if new token generated
        tokens["access_token"] = creds.token
        try:
            with open(clasprc_path, "w", encoding="utf-8") as f:
                json.dump(clasp_data, f, indent=2)
            logging.info("Updated ~/.clasprc.json with fresh access_token.")
        except Exception as e:
            logging.warning(f"Failed to update ~/.clasprc.json: {e}")

    return creds


def upload_spreadsheet(csv_path: Path, folder_id: str, title: str) -> dict:
    if not csv_path.exists():
        raise FileNotFoundError(f"Source CSV file not found: {csv_path}")

    creds = get_drive_credentials()
    drive_service = build("drive", "v3", credentials=creds)

    file_metadata = {
        "name": title,
        "mimeType": "application/vnd.google-apps.spreadsheet",
        "parents": [folder_id],
    }

    media = MediaFileUpload(
        str(csv_path),
        mimetype="text/csv",
        resumable=True,
    )

    logging.info(f"Uploading '{csv_path}' to Google Drive in folder '{folder_id}' as Google Spreadsheet '{title}'...")

    request = drive_service.files().create(
        body=file_metadata,
        media_body=media,
        fields="id, name, webViewLink",
        supportsAllDrives=True,
    )

    response = None
    while response is None:
        status, response = request.next_chunk()
        if status:
            logging.info(f"Upload progress: {int(status.progress() * 100)}%")

    file_id = response.get("id")
    web_link = response.get("webViewLink")
    logging.info(f"File uploaded successfully! ID: {file_id}")
    logging.info(f"URL: {web_link}")

    result = {
        "spreadsheet_id": file_id,
        "id": file_id,
        "name": response.get("name"),
        "webViewLink": web_link,
        "folder_id": folder_id,
    }

    METADATA_OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    with open(METADATA_OUTPUT_PATH, "w", encoding="utf-8") as f:
        json.dump(result, f, indent=2)

    logging.info(f"Saved metadata to {METADATA_OUTPUT_PATH}")
    return result


if __name__ == "__main__":
    upload_spreadsheet(CSV_FILE_PATH, TARGET_FOLDER_ID, SPREADSHEET_TITLE)
