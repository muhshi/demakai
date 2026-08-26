"""
export_onnx_model.py
--------------------
Utility script to export and quantize a sentence-transformer embedding model 
into an optimized ONNX (INT8) model for on-device Flutter inference.
"""

import os
import sys
import argparse

def export_to_onnx(
    model_name: str = "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2",
    output_dir: str = None,
    quantize: bool = True
):
    print("=" * 60)
    print(f"  PINTAR KBLI - ONNX Model Exporter")
    print(f"  Model Source: {model_name}")
    print("=" * 60)

    if not output_dir:
        _root_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
        output_dir = os.path.join(_root_dir, 'storage', 'app', 'models')

    os.makedirs(output_dir, exist_ok=True)
    onnx_path = os.path.join(output_dir, "kbli_model.onnx")
    onnx_quant_path = os.path.join(output_dir, "kbli_model_quantized.onnx")

    try:
        from transformers import AutoTokenizer, AutoModel  # type: ignore
        import torch  # type: ignore
    except ImportError:
        print("[ERROR] transformers and torch are required for model export.")
        print("Install via: pip install torch transformers onnx onnxruntime")
        return False

    print(f"1. Loading tokenizer & model: {model_name}...")
    tokenizer = AutoTokenizer.from_pretrained(model_name)
    model = AutoModel.from_pretrained(model_name)
    model.eval()

    print(f"2. Saving tokenizer vocabulary & config to: {output_dir}...")
    tokenizer.save_pretrained(output_dir)

    print("3. Exporting PyTorch model to ONNX format...")
    dummy_text = "Pertanian padi hibrida dan perkebunan kelapa sawit"
    inputs = tokenizer(
        dummy_text,
        padding=True,
        truncation=True,
        max_length=128,
        return_tensors="pt"
    )

    input_names = ["input_ids", "attention_mask"]
    dynamic_axes = {
        "input_ids": {0: "batch_size", 1: "sequence_length"},
        "attention_mask": {0: "batch_size", 1: "sequence_length"},
        "sentence_embedding": {0: "batch_size"},
    }

    if "token_type_ids" in inputs:
        input_names.append("token_type_ids")
        dynamic_axes["token_type_ids"] = {0: "batch_size", 1: "sequence_length"}

    # Define a wrapper that includes mean-pooling and l2 normalization directly in ONNX graph
    class SentenceEmbeddingWrapper(torch.nn.Module):
        def __init__(self, core_model):
            super().__init__()
            self.core_model = core_model

        def forward(self, input_ids, attention_mask, token_type_ids=None):
            if token_type_ids is not None:
                outputs = self.core_model(input_ids=input_ids, attention_mask=attention_mask, token_type_ids=token_type_ids)
            else:
                outputs = self.core_model(input_ids=input_ids, attention_mask=attention_mask)
            
            token_embeddings = outputs[0]  # First element of model_output contains all token embeddings
            input_mask_expanded = attention_mask.unsqueeze(-1).expand(token_embeddings.size()).float()
            sum_embeddings = torch.sum(token_embeddings * input_mask_expanded, 1)
            sum_mask = torch.clamp(input_mask_expanded.sum(1), min=1e-9)
            mean_pooled = sum_embeddings / sum_mask
            normalized = torch.nn.functional.normalize(mean_pooled, p=2, dim=1)
            return normalized

    wrapper = SentenceEmbeddingWrapper(model)
    wrapper.eval()

    inputs_tuple = tuple(inputs[k] for k in input_names)

    torch.onnx.export(
        wrapper,
        inputs_tuple,
        onnx_path,
        export_params=True,
        opset_version=14,
        do_constant_folding=True,
        input_names=input_names,
        output_names=["sentence_embedding"],
        dynamic_axes=dynamic_axes,
    )

    raw_size_mb = round(os.path.getsize(onnx_path) / (1024 * 1024), 2)
    print(f"   -> Exported raw ONNX model: {onnx_path} ({raw_size_mb} MB)")

    if quantize:
        try:
            from onnxruntime.quantization import quantize_dynamic, QuantType  # type: ignore
            print("4. Applying dynamic INT8 quantization for mobile optimization...")
            quantize_dynamic(
                model_input=onnx_path,
                model_output=onnx_quant_path,
                weight_type=QuantType.QInt8,
            )
            quant_size_mb = round(os.path.getsize(onnx_quant_path) / (1024 * 1024), 2)
            print(f"   -> Exported Quantized ONNX model: {onnx_quant_path} ({quant_size_mb} MB)")
            
            # Replace primary model with quantized version if desired
            import shutil
            shutil.copyfile(onnx_quant_path, onnx_path)
            print(f"   -> Active model set to quantized: {onnx_path}")
        except ImportError:
            print("[WARNING] onnxruntime not found. Skipping INT8 quantization step.")

    print("=" * 60)
    print("  ONNX MODEL EXPORT COMPLETE!")
    print(f"  Final Model Location: {onnx_path}")
    print("=" * 60)
    return True

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description="Export HuggingFace model to ONNX for Flutter mobile")
    parser.add_argument("--model", type=str, default="sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2")
    parser.add_argument("--no-quantize", action="store_true", help="Disable INT8 quantization")
    args = parser.parse_args()
    export_to_onnx(model_name=args.model, quantize=not args.no_quantize)
