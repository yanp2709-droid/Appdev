import json
from pathlib import Path

import pandas as pd
from sklearn.model_selection import train_test_split
from transformers import (
    AutoModelForSequenceClassification,
    AutoTokenizer,
    DataCollatorWithPadding,
    Trainer,
    TrainingArguments,
)
import numpy as np
from datasets import Dataset

DATA_PATH = Path(__file__).resolve().parent.parent / "data" / "emotion_dataset.csv"
MODEL_DIR = Path(__file__).resolve().parent.parent / "ml" / "artifacts"
MODEL_NAME = "distilbert-base-uncased"

LABELS = [
    "happy", "sad", "angry", "motivational", "fear", "depressing", "surprising",
    "stressed", "calm", "lonely", "romantic", "nostalgic", "mixed"
]
label2id = {label: idx for idx, label in enumerate(LABELS)}
id2label = {idx: label for label, idx in label2id.items()}


def compute_metrics(eval_pred):
    logits, labels = eval_pred
    preds = np.argmax(logits, axis=-1)
    accuracy = float((preds == labels).mean())
    return {"accuracy": accuracy}


def tokenize(batch, tokenizer):
    return tokenizer(batch["text"], truncation=True)


def main():
    MODEL_DIR.mkdir(parents=True, exist_ok=True)

    df = pd.read_csv(DATA_PATH)
    df["label_id"] = df["label"].map(label2id)

    train_df, eval_df = train_test_split(df, test_size=0.2, random_state=42, stratify=df["label_id"])

    train_ds = Dataset.from_pandas(train_df[["text", "label_id"]].rename(columns={"label_id": "labels"}))
    eval_ds = Dataset.from_pandas(eval_df[["text", "label_id"]].rename(columns={"label_id": "labels"}))

    tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME)
    train_ds = train_ds.map(lambda x: tokenize(x, tokenizer), batched=True)
    eval_ds = eval_ds.map(lambda x: tokenize(x, tokenizer), batched=True)

    model = AutoModelForSequenceClassification.from_pretrained(
        MODEL_NAME,
        num_labels=len(LABELS),
        id2label=id2label,
        label2id=label2id,
    )

    args = TrainingArguments(
        output_dir=str(MODEL_DIR),
        eval_strategy="epoch",
        learning_rate=2e-5,
        per_device_train_batch_size=8,
        per_device_eval_batch_size=8,
        num_train_epochs=4,
        weight_decay=0.01,
        logging_steps=10,
        save_strategy="epoch",
        load_best_model_at_end=True,
        metric_for_best_model="accuracy",
    )

    trainer = Trainer(
        model=model,
        args=args,
        train_dataset=train_ds,
        eval_dataset=eval_ds,
        tokenizer=tokenizer,
        data_collator=DataCollatorWithPadding(tokenizer=tokenizer),
        compute_metrics=compute_metrics,
    )

    trainer.train()
    metrics = trainer.evaluate()
    trainer.save_model(str(MODEL_DIR))
    tokenizer.save_pretrained(str(MODEL_DIR))

    with (MODEL_DIR / "metrics.json").open("w", encoding="utf-8") as fp:
        json.dump(metrics, fp, indent=2)

    print("Training complete. Metrics saved to", MODEL_DIR / "metrics.json")


if __name__ == "__main__":
    main()
