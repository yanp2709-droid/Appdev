# BERT Training Documentation

## Dataset

- File: `backend/data/emotion_dataset.csv`
- Labels: happy, sad, angry, motivational, fear, depressing, surprising, stressed, calm, lonely, romantic, nostalgic, mixed

## Training script

- File: `backend/ml/train_bert.py`
- Model: `distilbert-base-uncased`
- Split: 80/20 stratified
- Epochs: 4
- Learning rate: `2e-5`
- Batch size: 8

## Run training

```powershell
cd emotune\backend
pip install -r ml\requirements-ml.txt
python ml\train_bert.py
```

Artifacts are saved in `backend/ml/artifacts/`.

## Notes for capstone-quality model

- Expand dataset to at least 500-1000 balanced prompts per emotion.
- Add confusion matrix and per-class F1 in final paper.
- Evaluate with held-out validation and optional human evaluation.
