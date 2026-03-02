# Training Results

Current repository includes a **training pipeline** and a **starter dataset**.

Because model fine-tuning requires downloading pretrained weights and can take time/hardware, the committed metrics file is a template:

- `backend/ml/sample_metrics.json`

After running training, copy generated metrics from:

- `backend/ml/artifacts/metrics.json`

into your capstone report tables.

Recommended report sections:

1. Overall accuracy
2. Per-emotion precision/recall/F1
3. Confusion matrix
4. Error analysis (mixed/sad/depressing boundary cases)
