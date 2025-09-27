# Lokalni mini sistem za rođendanske čestitke

## Instalacija

### Hostinger (hPanel Composer)
1. Prijavite se na hPanel i otvorite **Website** → **Composer** za svoj sajt.
2. U root direktorijumu projekta pokrenite instalaciju zavisnosti (Install) kako bi se kreirao `vendor/` folder sa paketima iz `composer.json`.
3. Uverite se da direktorijum `data/` ima prava pisanja (npr. 0755 ili 0775) kako bi PHP mogao da kreira Excel fajlove tokom rada aplikacije.
4. U root direktorijumu napravite `.env` fajl sa vrednošću `OPENAI_API_KEY=...` (po potrebi dodajte i `OPENAI_MODEL=`).

### Lokalno razvijanje i upload
1. Instalirajte PHP 8 ili noviji i Composer na lokalnom računaru.
2. U root folderu projekta pokrenite `composer install` (ili `composer update`) kako bi se preuzele zavisnosti.
3. Upload-ujte ceo projekat **zajedno sa** generisanim `vendor/` direktorijumom na server.
4. Nakon prenosa proverite da `data/` direktorijum na serveru ima prava pisanja (0755 ili 0775) kako bi se Excel fajlovi mogli kreirati.
5. Na serveru napravite `.env` fajl sa `OPENAI_API_KEY=...` i opciono `OPENAI_MODEL=`.

## Podešavanje
- Ne čuvaju se nikakvi `.xlsx` fajlovi u repozitorijumu; oni se automatski kreiraju pri prvom upisu.
- Direktorijum `data/` mora ostati upisiv za PHP procese.
- Lista važećih šifara nalazi se u `data/valid_codes.txt` (jedan kod po liniji).
- Pitanja forme definišu se u `data/form.json`.

## Pokretanje lokalno
1. U root direktorijumu pokrenite `php -S localhost:8000`.
2. Otvorite `http://localhost:8000/index.html` u pregledaču.

Chat UI je dostupan preko linka „Postavi nam pitanje“ sa početne stranice.
