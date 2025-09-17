#!/usr/bin/env bash
# sync_to_github.sh
# مزامنة آمنة من الخادم إلى GitHub إذا كان الريموت متأخرًا عن ملفات الخادم.

set -euo pipefail

# إعدادات اختيارية عبر المتغيرات أو الوسائط
BRANCH="${1:-$(git rev-parse --abbrev-ref HEAD)}"
REMOTE="${REMOTE:-origin}"
COMMIT_MSG="${COMMIT_MSG:-sync: auto commit $(date -Iseconds) [host $(hostname)]}"
AUTHOR_NAME="${AUTHOR_NAME:-$(git config user.name || true)}"
AUTHOR_EMAIL="${AUTHOR_EMAIL:-$(git config user.email || true)}"

# قفل لمنع تشغيل متوازٍ
LOCK=".git/.sync.lock"
if [[ -e "$LOCK" ]]; then
  echo "قيد التشغيل: $LOCK موجود."
  exit 1
fi
trap 'rm -f "$LOCK"' EXIT
touch "$LOCK"

# تحقق مبدئي
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || { echo "ليست مستودع Git."; exit 1; }
git remote get-url "$REMOTE" >/dev/null 2>&1 || { echo "الريموت $REMOTE غير موجود."; exit 1; }

# ضبط الهوية إذا لزم
if [[ -z "$AUTHOR_NAME" || -z "$AUTHOR_EMAIL" ]]; then
  git config user.name "${AUTHOR_NAME:-auto-sync}"
  git config user.email "${AUTHOR_EMAIL:-auto-sync@local}"
fi

# تأكد من الفرع المستهدف
git checkout "$BRANCH" >/dev/null 2>&1 || { echo "لا يمكن التبديل إلى الفرع $BRANCH."; exit 1; }

# اجلب آخر حالة من الريموت
git fetch "$REMOTE" "$BRANCH" --prune

# التقط التغييرات المحلية إن وجدت
if ! git diff --quiet || ! git diff --cached --quiet || [[ -n "$(git ls-files --others --exclude-standard)" ]]; then
  git add -A
  git commit -m "$COMMIT_MSG" || true
fi

# إعادة الجلب بعد الالتقاط
git fetch "$REMOTE" "$BRANCH" --prune

# احسب الفروقات: يسار=محلي متقدم، يمين=ريموت متقدم
read -r AHEAD BEHIND < <(git rev-list --left-right --count HEAD..."$REMOTE/$BRANCH" | awk '{print $1, $2}')

# إن كان الريموت متقدمًا، اسحب بإعادة ترتيب التاريخ مع حماية تلقائية
if [[ "${BEHIND:-0}" -gt 0 ]]; then
  # في حالات نادرة مع تعديلات غير مدمجة، استعمل stash مؤقتًا
  STASHED=0
  if ! git diff --quiet || ! git diff --cached --quiet; then
    git stash push -u -m "pre-sync $(date -Iseconds)"
    STASHED=1
  fi
  git pull --rebase "$REMOTE" "$BRANCH"
  if [[ $STASHED -eq 1 ]]; then
    git stash pop || { echo "تعذر دمج stash تلقائيًا. عالج التعارض ثم ادفع يدويًا."; exit 1; }
  fi
fi

# ادفع إذا كان لدينا التزامات محلية غير موجودة على الريموت
read -r AHEAD2 BEHIND2 < <(git rev-list --left-right --count HEAD..."$REMOTE/$BRANCH" | awk '{print $1, $2}')

if [[ "${AHEAD2:-0}" -gt 0 ]]; then
  git push "$REMOTE" "$BRANCH"
  echo "تم الدفع إلى $REMOTE/$BRANCH. [ahead=$AHEAD2, behind=$BEHIND2]"
else
  echo "لا حاجة للدفع. المستودع متزامن. [ahead=$AHEAD2, behind=$BEHIND2]"
fi
