"""
Deploy Google Apps Script project bound to merged SE2026 Google Spreadsheet.
Uploads Code_5Baris.gs and Index_5Baris.html, configuring appsscript.json manifest.
Credentials: ~/.clasprc.json
"""

import json
import logging
from pathlib import Path

from google.oauth2.credentials import Credentials
from google.auth.transport.requests import Request
from googleapiclient.discovery import build

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")

METADATA_PATH = Path("storage/app/se2026_resolved/spreadsheet_metadata.json")
DEPLOYMENT_OUTPUT_PATH = Path("storage/app/se2026_resolved/apps_script_deployment.json")
CODE_GS_PATH = Path("KBLI SE2026/Code_5Baris.gs")
INDEX_HTML_PATH = Path("KBLI SE2026/Index_5Baris.html")
PROJECT_TITLE = "PINTAR_KBLI_SE2026_VERIFIKASI"


def get_oauth_credentials() -> Credentials:
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
        tokens["access_token"] = creds.token
        try:
            with open(clasprc_path, "w", encoding="utf-8") as f:
                json.dump(clasp_data, f, indent=2)
            logging.info("Updated ~/.clasprc.json with fresh access_token.")
        except Exception as e:
            logging.warning(f"Failed to update ~/.clasprc.json: {e}")

    return creds


def deploy_apps_script() -> dict:
    if not METADATA_PATH.exists():
        raise FileNotFoundError(f"Spreadsheet metadata file not found: {METADATA_PATH}")

    with open(METADATA_PATH, "r", encoding="utf-8") as f:
        meta = json.load(f)

    spreadsheet_id = meta.get("spreadsheet_id") or meta.get("id")
    spreadsheet_url = meta.get("webViewLink", f"https://docs.google.com/spreadsheets/d/{spreadsheet_id}/edit")

    if not spreadsheet_id:
        raise ValueError(f"spreadsheet_id missing in {METADATA_PATH}")

    if not CODE_GS_PATH.exists():
        raise FileNotFoundError(f"Code file not found: {CODE_GS_PATH}")

    if not INDEX_HTML_PATH.exists():
        raise FileNotFoundError(f"HTML file not found: {INDEX_HTML_PATH}")

    with open(CODE_GS_PATH, "r", encoding="utf-8") as f:
        code_content = f.read()

    with open(INDEX_HTML_PATH, "r", encoding="utf-8") as f:
        html_content = f.read()

    creds = get_oauth_credentials()
    script_service = build("script", "v1", credentials=creds)

    manifest = {
        "timeZone": "Asia/Jakarta",
        "dependencies": {},
        "exceptionLogging": "STACKDRIVER",
        "runtimeVersion": "V8",
        "webapp": {
            "executeAs": "USER_ACCESSING",
            "access": "ANYONE"
        }
    }

    # Check if we already created a script project
    existing_script_id = None
    if DEPLOYMENT_OUTPUT_PATH.exists():
        try:
            with open(DEPLOYMENT_OUTPUT_PATH, "r", encoding="utf-8") as f:
                deploy_info = json.load(f)
                if deploy_info.get("spreadsheetId") == spreadsheet_id:
                    existing_script_id = deploy_info.get("scriptId")
        except Exception:
            pass

    script_id = None
    if existing_script_id:
        try:
            logging.info(f"Checking existing project: {existing_script_id}...")
            proj = script_service.projects().get(scriptId=existing_script_id).execute()
            script_id = proj.get("scriptId")
            logging.info(f"Found existing Apps Script project: {script_id}")
        except Exception as e:
            logging.warning(f"Could not retrieve existing script project: {e}. Will create a new one.")
            script_id = None

    if not script_id:
        logging.info(f"Creating container-bound Apps Script project for spreadsheet {spreadsheet_id}...")
        create_body = {
            "title": PROJECT_TITLE,
            "parentId": spreadsheet_id
        }
        project = script_service.projects().create(body=create_body).execute()
        script_id = project.get("scriptId")
        logging.info(f"Created Apps Script project with scriptId: {script_id}")

    logging.info("Updating Apps Script project files (manifest, Code, Index_5Baris)...")
    content_payload = {
        "files": [
            {
                "name": "appsscript",
                "type": "JSON",
                "source": json.dumps(manifest, indent=2)
            },
            {
                "name": "Code",
                "type": "SERVER_JS",
                "source": code_content
            },
            {
                "name": "Index_5Baris",
                "type": "HTML",
                "source": html_content
            }
        ]
    }

    script_service.projects().updateContent(scriptId=script_id, body=content_payload).execute()
    logging.info("Project content updated successfully!")

    editor_url = f"https://script.google.com/d/{script_id}/edit"
    result = {
        "scriptId": script_id,
        "spreadsheetId": spreadsheet_id,
        "spreadsheetUrl": spreadsheet_url,
        "scriptEditorUrl": editor_url,
        "title": PROJECT_TITLE
    }

    DEPLOYMENT_OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    with open(DEPLOYMENT_OUTPUT_PATH, "w", encoding="utf-8") as f:
        json.dump(result, f, indent=2)

    logging.info(f"Deployment info saved to {DEPLOYMENT_OUTPUT_PATH}")
    logging.info(f"Apps Script Editor URL: {editor_url}")
    logging.info(f"Spreadsheet URL: {spreadsheet_url}")

    return result


if __name__ == "__main__":
    deploy_apps_script()
