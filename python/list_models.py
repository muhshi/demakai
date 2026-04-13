import google.generativeai as genai
import os
from dotenv import load_dotenv

# Load .env
_env_path = os.path.join(os.path.dirname(__file__), '..', '.env')
load_dotenv(dotenv_path=_env_path)

api_key = os.getenv("GEMINI_API_KEY")
if not api_key:
    print("API Key not found!")
    exit()

genai.configure(api_key=api_key)

print("Listing supported models for 'embedContent':")
for m in genai.list_models():
    if 'embedContent' in m.supported_generation_methods:
        print(f" - {m.name}")
