"""
export_onnx_model.py — On-Device AI Model Exporter for Flutter
================================================================
Mengekspor model Transformer Multilingual ke format ONNX INT8 Quantized
agar dapat dijalankan secara offline di aplikasi Flutter (menggunakan onnxruntime_flutter).

Cara penggunaan:
    pip install torch transformers onnx onnxruntime
    python python/utils/export_onnx_model.py --model sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2
"""

import os
import sys
import argparse
from pathlib import Path

def export_to_onnx(model_name: str, output_dir: str):
    print(f"=== PINTAR KBLI - ONNX Model Exporter ===")
    print(f"Model Source: {model_name}")
    print(f"Output Dir:   {output_dir}")

    try:
        import torch
        from transformers import AutoTokenizer, AutoModel
    except ImportError:
        print("[ERROR] Pastikan torch dan transformers terinstall:")
        print("  pip install torch transformers onnx onnxruntime")
        return False

    out_path = Path(output_dir)
    out_path.mkdir(parents=True, exist_ok=True)

    raw_onnx_path = out_path / "model_raw.onnx"
    quant_onnx_path = out_path / "kbli_model.onnx"

    print("1. Mengunduh tokenizer dan model...")
    tokenizer = AutoTokenizer.from_pretrained(model_name)
    model = AutoModel.from_pretrained(model_name)
    model.eval()

    # Simpan vocab / tokenizer files untuk mobile jika diperlukan
    tokenizer.save_pretrained(str(out_path / "tokenizer"))

    print("2. Mengekspor model PyTorch ke format ONNX...")
    dummy_text = "kegiatan perdagangan eceran beras dan sembako"
    inputs = tokenizer(dummy_text, return_tensors="pt", padding=True, truncation=True, max_length=128)

    torch.onnx.export(
        model,
        (inputs["input_ids"], inputs["attention_mask"]),
        str(raw_onnx_path),
        input_names=["input_ids", "attention_mask"],
        output_names=["last_hidden_state"],
        dynamic_axes={
            "input_ids": {0: "batch_size", 1: "sequence_length"},
            "attention_mask": {0: "batch_size", 1: "sequence_length"},
            "last_hidden_state": {0: "batch_size", 1: "sequence_length"},
        },
        opset_version=14,
        do_constant_folding=True,
    )
    raw_size_mb = os.path.getsize(raw_onnx_path) / (1024 * 1024)
    print(f"   -> Raw ONNX Model: {raw_size_mb:.2f} MB")

    print("3. Melakukan Dynamic INT8 Quantization (Optimasi untuk Mobile CPU)...")
    try:
        from onnxruntime.quantization import quantize_dynamic, QuantType
        quantize_dynamic(
            model_input=str(raw_onnx_path),
            model_output=str(quant_onnx_path),
            weight_type=QuantType.QInt8,
        )
        quant_size_mb = os.path.getsize(quant_onnx_path) / (1024 * 1024)
        print(f"   -> Quantized ONNX Model: {quant_size_mb:.2f} MB")

        # Hapus file raw agar hemat penyimpanan
        if os.path.exists(raw_onnx_path):
            os.remove(raw_onnx_path)

        print("=================================================")
        print(f"  SUCCESS! Model siap digunakan di Flutter:")
        print(f"  File: {quant_onnx_path} ({quant_size_mb:.2f} MB)")
        print("=================================================")
        return True

    except Exception as e:
        print(f"[WARNING] Gagal melakukan kuantisasi: {e}")
        print(f"Model raw tetap tersedia di {raw_onnx_path}")
        return True

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Export Transformer to Quantized ONNX for Flutter")
    parser.add_argument(
        "--model",
        type=str,
        default="sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2",
        help="HuggingFace model identifier",
    )
    parser.add_argument(
        "--out",
        type=str,
        default=os.path.join(os.path.dirname(__file__), "../../storage/app/models"),
        help="Target output directory",
    )
    args = parser.parse_args()
    export_to_onnx(args.model, args.out)
