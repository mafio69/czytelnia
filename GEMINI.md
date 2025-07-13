#### Pamiętaj, by rozmawiać po polsku
#### Mam na imię Mariusz, mam 55 lat, byłem programistą php
#### Ciebie będę nazywał Adam


Faza 1: Przygotowanie środowiska i instalacja
zależności


1. Baza Danych: Skonfigurujemy połączenie z bazą
   danych (np. SQLite, żeby było prosto na początek) w
   pliku .env.
2. Instalacja Komponentów: Zainstalujemy niezbędne
   pakiety Symfony za pomocą composer:
    * doctrine/orm-pack: Do obsługi bazy danych i
      tworzenia modeli (encje).
    * symfony/form i symfony/validator: Do łatwego
      tworzenia i walidacji formularza, w którym
      będziesz wklejać URL.
    * symfony/twig-bundle: Do tworzenia widoków
      (szablonów HTML).
    * symfony/http-client: Do pobierania treści
      artykułu ze wskazanego adresu URL.

Faza 2: Rdzeń aplikacji (Backend)


1. Encja Doctrine: Stworzymy encję ArticleSummary,
   która będzie reprezentować nasz artykuł w bazie
   danych. Będzie zawierać pola takie jak id,
   originalUrl, summary (streszczenie) i createdAt.
   Użyjemy do tego komendy php bin/console
   make:entity.
2. Migracja Bazy Danych: Wygenerujemy i uruchomimy
   migrację, aby stworzyć odpowiednią tabelę w bazie
   danych na podstawie naszej encji.
3. Kontroler: Stworzymy SummaryController, który
   będzie obsługiwał logikę aplikacji:
    * Jedna akcja do wyświetlania formularza i obsługi
      jego wysłania.
    * Druga akcja do wyświetlania listy wszystkich
      zapisanych streszczeń.
4. Serwis do streszczania: Stworzymy dedykowaną klasę
   (serwis), np. SummarizationService, która będzie
   odpowiedzialna za:
    * Pobranie treści strony (HTML) podanym URL-em.
    * "Oczyszczenie" HTML, aby uzyskać sam tekst
      artykułu.
    * Wysłanie zapytania do zewnętrznego API (np.
      Gemini API) z prośbą o streszczenie tekstu.
    * Ważne: Będziesz potrzebował klucza API do usługi
      SI. Zapiszemy go bezpiecznie w pliku .env.

Faza 3: Interfejs użytkownika (Frontend)



1. Formularz: Zbudujemy klasę formularza
   (SummaryFormType), aby Symfony mogło automatycznie
   wygenerować formularz HTML.
2. Szablony Twig: Stworzymy dwa proste widoki:
    * summary/new.html.twig: Strona z formularzem do
      wklejenia linku.
    * summary/index.html.twig: Strona wyświetlająca
      listę zapisanych streszczeń.
    * Dodamy też podstawowy szablon base.html.twig z
      prostym menu i stylami (możemy użyć np.
      Bootstrapa, żeby wyglądało schludnie).
