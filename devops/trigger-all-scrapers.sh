#!/bin/bash
# Trigger all scrapers from repos with topic "job-seeker-ro-spider"

set -e

REPOS=(
  # sebiboga repos
  "sebiboga/rapel-srl-nodejs-scraper:scrape.yml"
  "sebiboga/e-infra-sa-python-scraper:scraper.yml"
  "sebiboga/mol-romania-petroleum-products-srl-nodejs-scraper:scrape.yml"
  "sebiboga/randstad-romania-nodejs-scraper:scrape.yml"
  "sebiboga/secpral-pro-instalatii-srl-nodejs-scraper:run-spishop.yml"
  "sebiboga/epam-systems-international-srl-nodejs-scraper:scrape.yml"
  "sebiboga/co-era-bc-srl-nodejs-scraper:scrape.yml"
  "sebiboga/artsoft-consult-srl-nodejs-scraper:scrape.yml"
  "sebiboga/bitdefender-srl-nodejs-scraper:scrape.yml"
  "sebiboga/tec-software-solutions-srl-nodejs-scraper:scrape.yml"
  "sebiboga/unix-auto-srl-nodejs-scraper:scrape.yml"
  "sebiboga/ropardo-srl-nodejs-scraper:scrape.yml"
  "sebiboga/ascom-mobile-solutions-romania-srl-nodejs-scraper:scrape.yml"
  "sebiboga/borgdesign-srl-nodejs-scraper:scrape.yml"
  "sebiboga/ciklum-romania-srl-nodejs-scraper:scrape.yml"
  "sebiboga/mejix-srl-nodejs-scraper:scrape.yml"
  "sebiboga/endava-romania-srl-nodejs-scraper:scrape.yml"
  "sebiboga/tickbird-srl-nodejs-scraper:scrape.yml"
  "sebiboga/ahold-delhaize-technologies-srl-nodejs-scraper:scrape.yml"
  "sebiboga/msg-systems-romania-srl-nodejs-scraper:scrape.yml"
  # other users repos
  "cristian-alexutan/robert-bosch-srl-nodejs-scraper:scrape.yml"
  "BalaciSofia/frequentis-romania-srl-nodejs-scraper:scrape.yml"
  "emtreila/qubiz-srl-nodejs-scraper:scrape.yml"
  "BalaciSofia/rebeldot-solutions-srl-nodejs-scraper:scrape.yml"
  "cristian-alexutan/lateral-group-srl-nodejs-scraper:scrape.yml"
  "emtreila/yonder-srl-nodejs-scraper:scrape.yml"
  "carmenioanaa/arrise-srl-nodejs-scraper:scrape.yml"
  "AlexColceriu/eighteengym-srl-nodejs-scraper:scrape.yml"
  "peviitor-ro/Scrapers_Cristi_Olteanu_2:scrapers_runner.yml"
)

echo "Triggering all scrapers..."

for entry in "${REPOS[@]}"; do
  REPO="${entry%%:*}"
  WORKFLOW="${entry##*:}"

  echo -n "→ $REPO ($WORKFLOW) ... "
  if gh workflow run "$WORKFLOW" --repo "$REPO" 2>/dev/null; then
    echo "✅ triggered"
  else
    echo "❌ failed"
  fi
done

echo ""
echo "Done! All scrapers triggered."
