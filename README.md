# CashCue — Explainable Commitment Checker

CashCue is a web-based FinTech prototype for UMPSA Hackathon X: FinTech Forward 2026. It helps users decide whether a new monthly commitment is safe before they agree to it.

## Problem Statement

Many students and young earners make monthly commitments such as phone instalments, motorcycles, subscriptions, or loans without checking how the payment affects their salary balance. This can lead to weak savings, poor emergency backup, and long-term financial stress.

## Solution

CashCue guides users through four simple steps:

1. Save salary profile: income, basic needs, monthly savings, and emergency savings.
2. Add existing commitments: current monthly payments.
3. Add goals: planned saving targets.
4. Check a new commitment: CashCue gives a decision before the user commits.

The system gives one clear result:

- Proceed
- Proceed Carefully
- Reduce
- Delay
- Avoid

## Demo Flow

```text
index.php → profile.php → goal.php → check.php → result.php
```

Recommended demo data:

```text
Monthly income: RM4200
Basic needs: RM1800
Monthly savings: RM600
Emergency savings: RM3500
Existing commitment: Car RM500
Goal: Laptop RM3000, saved RM600, timeline 12 months
New commitment: Motorcycle RM300 for 24 months
```

## Key Features

- Step-by-step guided checking flow.
- Explainable rule-based AI decision logic.
- 50/30/20-inspired salary allocation guide.
- Commitment score and decision category.
- Goal saving calculation.
- Emergency savings risk consideration.
- Profile history saved in database.
- Soft delete for commitments and goals, so deleted items are hidden from UI but remain in database history.
- BM and English interface support.
- Local chart fallback, so the result chart still works without internet.

## AI Tools Used

CashCue uses a hybrid AI approach that combines explainable rule-based financial logic with an external AI API.

First, the system calculates the user’s financial readiness using transparent rule-based logic. The calculation considers:

- commitment ratio,
- savings rate,
- emergency fund coverage,
- goal saving pressure,
- duration risk,
- remaining monthly balance,
- safer new commitment limit.

Based on these factors, CashCue produces a financial decision such as **Proceed**, **Proceed Carefully**, **Reduce**, **Delay**, or **Avoid**. This rule-based logic ensures that the result is explainable and consistent, which is important for financial decision support.

After the decision is generated, CashCue sends the financial summary to an AI API through `ai_advice.php`. The AI API generates simple personalized advice in natural language so users can better understand the result and know what action to take next.

This approach allows CashCue to remain transparent while still using AI to improve user understanding and guidance.

## Technology Used

- PHP
- MySQL / phpMyAdmin
- HTML
- CSS
- JavaScript
- XAMPP

## Database

Database name:

```text
cashcue_db
```

Tables:

```text
user_profiles
user_profile_history
commitments
goals
goal_items
new_commitments
commitment_checks
```

The database and tables are created automatically by `config.php`. You can also import `database.sql` manually in phpMyAdmin.

## Setup Steps

1. Copy the `cashcue_php` folder into:

```text
C:/xampp/htdocs/
```

2. Start XAMPP:

```text
Apache = Start
MySQL = Start
```

3. Open the system:

```text
http://localhost/cashcue_php/
```

If the files are placed directly inside `htdocs`, open:

```text
http://localhost/
```

## Business Potential

CashCue can be expanded into a student and young worker financial readiness tool. It can support banks, universities, and youth financial literacy programs by helping users understand affordability before taking commitments.

Possible future model:

- freemium user checking tool,
- university financial literacy partnership,
- bank/fintech integration for affordability education,
- anonymised financial habit insights dashboard.

## Known Limitations

- The current version is a prototype for hackathon demo.
- It does not connect to real bank accounts.
- It uses explainable rule-based logic instead of machine learning.
- The recommendation is educational support, not official financial advice.

## Team Q&A Explanation

CashCue uses explainable rule-based AI logic. It is not a black-box model. The system calculates commitment ratio, savings rate, emergency fund coverage, goal pressure, duration risk, and remaining balance. Based on those factors, it gives a clear decision: Proceed, Proceed Carefully, Reduce, Delay, or Avoid. We chose this method because financial guidance should be transparent and easy for young users to understand.
