# Lokalni mini sistem za rođendanske čestitke

## Instalacija

### Opcija 1: Hostinger hPanel
1. Ulogujte se na hPanel i otvorite opciju **Composer** za željeni sajt.
2. Postavite root direktorijum sajta na projekat i pokrenite komandu **Install** (ova komanda poziva `composer install`).
3. Proverite da je direktorijum `data/` na serveru upisiv (preporučene dozvole su `0755` ili `0775`).
4. Postavite `.env` fajl sa OpenAI podešavanjima (vidi dole).

### Opcija 2: Lokalno pa upload
1. Lokalno instalirajte PHP 8 ili noviji i Composer.
2. U root direktorijumu projekta pokrenite `composer install`.
3. Nakon instalacije otpremite ceo projekat, uključujući kompletan direktorijum `vendor/`, na Hostinger server.
4. Na serveru proverite da `data/` direktorijum ima dozvole za pisanje (npr. `0755` ili `0775`).
5. Dodajte `.env` fajl sa OpenAI podešavanjima.

## Konfiguracija okruženja

1. Kreirajte `.env` fajl u root direktorijumu sa sadržajem:
   ```env
   OPENAI_API_KEY=vas_kljuc
   # opciono: OPENAI_MODEL=gpt-5-mini
   ```
2. Direktoriјum `data/` mora da ostane prazan (osim tekstualnih fajlova) jer PHP aplikacija sama kreira `.xlsx` fajlove pri radu.

## Pokretanje lokalno

1. Pokrenite lokalni PHP server komandom `php -S localhost:8000` iz root foldera.
2. Aplikacija će biti dostupna na `http://localhost:8000/index.html`.
