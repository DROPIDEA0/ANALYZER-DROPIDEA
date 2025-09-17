#!/usr/bin/env bash
# sync_to_github.sh
# مزامنة آمنة من الخادم إلى GitHub إذا كان الريموت متأخرًا. خالٍ من process substitution.

set -euo pipefail

# ---------------------- إعدادات قابلة للتعديل ----------------------
REMOTE="${REMOTE:-origin}"
DEFAULT_REPO_URL="${DEFAULT_REPO_URL:-git@github.com:DROPIDEA0/ANALYZER-DROPIDEA.git}"
COMMIT_MSG="${COMMIT_MSG:-sync: auto commit $(date -Iseconds) [host $(hostname)]}"
AUTHOR_NAME="${AUTHOR_NAME:-$(git config user.name || true)}"
AUTHOR_EMAIL="${AUTHOR_EMAIL:-$(git config user.email || true)}"
# -------------------------------------------------------------------

# قفل لمنع التشغيل المتوازي
LOCK=".git/.sync.lock"
if [[ -e "$LOCK" ]]; then
  echo "مهمة مزامنة قيد التشغيل. ألغِ العملية السابقة أو احذف $LOCK"
  exit 1
fi
trap 'rm -f "$LOCK"' EXIT
touch "$LOCK"

# تحقق أننا داخل مستودع Git
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || { echo "ليست داخل مستودع Git."; exit 1; }

# تأكد من وجود ريموت origin
if ! git remote get-url "$REMOTE" >/dev/null 2>&1; then
  echo "الريموت $REMOTE غير موجود. سيتم ضبطه على: $DEFAULT_REPO_URL"
  git remote add "$REMOTE" "$DEFAULT_REPO_URL"
fi

# اضبط URL الريموت إذا رغبت عبر المتغير
if [[ -n "${GITHUB_REPO_URL:-}" ]]; then
  git remote set-url "$REMOTE" "$GITHUB_REPO_URL"
fi

# فرع العمل
BRANCH="${1:-$(git rev-parse --abbrev-ref HEAD)}"
if [[ -z "$BRANCH" || "$BRANCH" == "HEAD" ]]; then
  BRANCH="main"
fi

# ضبط الهوية إن لزم
if [[ -z "$AUTHOR_NAME" || -z "$AUTHOR_EMAIL" ]]; then
  git config user.name "${AUTHOR_NAME:-auto-sync}"
  git config user.email "${AUTHOR_EMAIL:-auto-sync@local}"
fi

# اجلب آخر حالة من الريموت
git fetch "$REMOTE" "$BRANCH" --prune || true

# التقط التغييرات المحلية (غير المتتبعة + المعدلة)
if ! git diff --quiet || ! git diff --cached --quiet || [[ -n "$(git ls-files --others --exclude-standard)" ]]; then
  git add -A
  git commit -m "$COMMIT_MSG" || true
fi

# اجلب مجددًا بعد الالتقاط
git fetch "$REMOTE" "$BRANCH" --prune || true

# احسب الفروقات بدون process substitution
AHEAD_BEHIND="$(git rev-list --left-right --count HEAD..."$REMOTE/$BRANCH" 2>/dev/null || echo "0 0")"
AHEAD="$(echo "$AHEAD_BEHIND" | awk '{print $1}')"
BEHIND="$(echo "$AHEAD_BEHIND" | awk '{print $2}')"

# لو الريموت متقدم علينا → اسحب بإعادة ترتيب التاريخ
if [[ "${BEHIND:-0}" -gt 0 ]]; then
  STASHED=0
  if ! git diff --quiet || ! git diff --cached --quiet || [[ -n "$(git ls-files --others --exclude-standard)" ]]; then
    git stash push -u -m "pre-sync $(date -Iseconds)" >/dev/null || true
    STASHED=1
  fi

  # حاول إعادة الترتيب
  if ! git pull --rebase "$REMOTE" "$BRANCH"; then
    echo "تعذر rebase تلقائي. سيُجرى دمج عادي."
    git pull --no-rebase "$REMOTE" "$BRANCH"
  fi

  if [[ $STASHED -eq 1 ]]; then
    git stash pop || { echo "تعذر دمج stash تلقائيًا. عالج التعارض ثم ادفع يدويًا."; exit 1; }
  fi
fi

# احسب الفروقات مرة أخرى
AHEAD_BEHIND2="$(git rev-list --left-right --count HEAD..."$REMOTE/$BRANCH" 2>/dev/null || echo "0 0")"
AHEAD2="$(echo "$AHEAD_BEHIND2" | awk '{print $1}')"
BEHIND2="$(echo "$AHEAD_BEHIND2" | awk '{print $2}')"

# ادفع إن كنا متقدمين
if [[ "${AHEAD2:-0}" -gt 0 ]]; then
  git push "$REMOTE" "$BRANCH"
  echo "تم الدفع إلى $REMOTE/$BRANCH. [ahead=$AHEAD2, behind=$BEHIND2]"
else
  echo "لا حاجة للدفع. المستودع متزامن. [ahead=$AHEAD2, behind=$BEHIND2]"
fi
