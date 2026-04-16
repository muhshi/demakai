import google.generativeai as genai
import os
from dotenv import load_dotenv

# Load .env
_env_path = os.path.join(os.path.dirname(__file__), '..', '.env')
load_dotenv(dotenv_path=_env_path)

api_key = os.getenv("GEMINI_API_KEY")
genai.configure(api_key=api_key)

models_to_test = ["models/gemini-embedding-001", "models/gemini-embedding-2-preview"]

for m in models_to_test:
    print(f"Testing model: {m}")
    try:
        res = genai.embed_content(
            model=m,
            content="test query",
            task_type="retrieval_query",
            output_dimensionality=3072
        )
        vec = res["embedding"]
        print(f" -> Dimension with output_dimensionality=3072: {len(vec)}")
    except Exception as e:
        print(f" -> Error with output_dimensionality=3072: {e}")

    try:
        res = genai.embed_content(
            model=m,
            content="test query",
            task_type="retrieval_query"
        )
        vec = res["embedding"]
        print(f" -> Dimension default: {len(vec)}")
    except Exception as e:
        print(f" -> Error default: {e}")
