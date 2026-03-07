# Joomla 5 Schema.org - LocalBusiness Plugin

Profesjonalna wtyczka dla Joomla 5+ wprowadzająca obsługę danych strukturalnych **LocalBusiness** (Lokalna Firma) zgodnie z najnowszymi standardami Schema.org.

## Opis

Wtyczka integruje się z natywnym systemem Schema.org w Joomla 5, pozwalając na łatwe dodawanie szczegółowych informacji o firmie bezpośrednio w edycji artykułu. Generuje poprawny kod JSON-LD, który pomaga wyszukiwarkom (takim jak Google) lepiej zrozumieć i prezentować Twoją firmę w wynikach wyszukiwania (np. poprzez gwiazdki opinii, godziny otwarcia czy mapy).

## Główne Funkcje

- **Pełna zgodność z Joomla 5.x**: Wykorzystuje natywny system zdarzeń i subformularze.
- **Bogaty zestaw pól**: Obsługuje nazwy prawne, slogany, opisy, słowa kluczowe.
- **Dane Kontaktowe**: Wielokrotne telefony, e-maile, faksy.
- **Geolokalizacja**: Integracja z mapami, szerokość i długość geograficzna.
- **Godziny Otwarcia**: Elastyczny format zgodny ze Schema.org.
- **Social Media**: Możliwość dodania dowolnej liczby linków do profili społecznościowych (`sameAs`).
- **Gwiazdki w Google**: Pełna obsługa `AggregateRating` (średnia ocena i liczba opinii).
- **Identyfikatory Biznesowe**: NIP (VAT ID), REGON, DUNS, LEI, NAICS.
- **Pomoc Kontekstowa**: Każde pole posiada opis i pomoc w języku polskim.

## Instalacja

1. Pobierz repozytorium jako plik ZIP.
2. Zaloguj się do panelu administratora Joomla.
3. Przejdź do **System** -> **Zainstaluj** -> **Rozszerzenia**.
4. Wgraj pobrany plik ZIP.
5. Przejdź do **System** -> **Zarządzaj** -> **Wtyczki**.
6. Wyszukaj i włącz wtyczkę **Schemaorg - Lokalna Firma (LocalBusiness)**.

## Użycie

1. Otwórz artykuł, który ma reprezentować Twoją firmę lub kontakt.
2. Przejdź do zakładki **Schema**.
3. Z listy rozwijanej wybierz **LocalBusiness**.
4. Wypełnij pola, które Cię dotyczą.
5. Zapisz artykuł. Joomla automatycznie wygeneruje kod JSON-LD w nagłówku strony.

## Wymagania

- Joomla 5.0 lub nowsza.
- Włączony systemowy dodatek Schema.org (standardowo w Joomla 5).

## Autor

**Paweł Półtoraczyk**
- WWW: [web-service.com.pl](https://web-service.com.pl)
- GitHub: [pablop76](https://github.com/pablop76)

## Licencja

Wtyczka jest udostępniana na licencji GNU General Public License v2 lub późniejszej.
