import sys
import os

try:
    from fastapi import FastAPI
    print("FastAPI: OK")
    import uvicorn
    print("Uvicorn: OK")
    import psycopg2
    print("Psycopg2: OK")
    import google.generativeai
    print("Google GenerativeAI: OK")
    import Sastrawi
    print("Sastrawi: OK")
    
    # Try importing local modules
    sys.path.insert(0, os.path.dirname(__file__))
    from preprocessing.basic import preprocess_basic
    from search.sql_like import search_raw
    print("Local modules: OK")
    
except Exception as e:
    print(f"Error: {e}")
    sys.exit(1)
