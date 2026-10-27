# VeciGo · Tu barrio pide juntos

App de pedidos locales del barrio (PHP + MySQL). Catálogo → pedido → paneles por rol (cliente, comercio, admin) con **login unificado por correo**.

**Ficha 3260319** · Liceo El Gran Virrey · Paula Mantilla, Santiago Urueña, David Patarroyo

---

## URL local

- App: http://localhost/vecigo/
- Locales / mapa: http://localhost/vecigo/locales.php · http://localhost/vecigo/locales_mapa.php
- Carrito: http://localhost/vecigo/carrito.php
- Login: http://localhost/vecigo/login.php
- Registro cliente: http://localhost/vecigo/registro.php
- Registrar local (mapa + dirección): http://localhost/vecigo/registro_comercio.php
- Estado BD: http://localhost/vecigo/probar_conexion.php

## Credenciales demo

Un solo login: el correo define el panel.

| Rol | Correo | Contraseña |
|---|---|---|
| Cliente | `carlos@vecigo.co` | `cliente2024` |
| Comercio Doña Rosa | `donarosa@vecigo.co` | `comercio2024` |
| Comercio La Esquina | `hamburguesas@vecigo.co` | `comercio2024` |
| Comercio El Trigal | `trigal@vecigo.co` | `comercio2024` |
| Admin | `admin@vecigo.co` | `vecigo2024` |

---

## Arranque rápido (XAMPP)

1. Enciende **Apache** y **MySQL**.
2. Instalación limpia:

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root -h 127.0.0.1 < /Applications/XAMPP/xamppfiles/htdocs/vecigo/bd/vecigo.sql
```

Si ya tenías VeciGo, aplica migraciones en orden:

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root -h 127.0.0.1 vecigo < /Applications/XAMPP/xamppfiles/htdocs/vecigo/bd/migracion_panel_comercio.sql
/Applications/XAMPP/xamppfiles/bin/mysql -u root -h 127.0.0.1 vecigo < /Applications/XAMPP/xamppfiles/htdocs/vecigo/bd/migracion_login_unificado.sql
/Applications/XAMPP/xamppfiles/bin/mysql -u root -h 127.0.0.1 vecigo < /Applications/XAMPP/xamppfiles/htdocs/vecigo/bd/migracion_panel_cliente.sql
/Applications/XAMPP/xamppfiles/bin/mysql -u root -h 127.0.0.1 vecigo < /Applications/XAMPP/xamppfiles/htdocs/vecigo/bd/migracion_locales_mapa.sql
```

3. Abre http://localhost/vecigo/

---

## Flujo de la app

1. **Cliente** — crea cuenta / entra → pide en comercios (datos precargados) → ve pedidos y edita su info
2. **Comercio** — bandeja del local + menú (agotados / alta)
3. **Admin** — vista global de pedidos del barrio

## Tablas

`categoria_comercio` → `comercio` → `producto` / `pedido` → `pedido_item` · `usuario` (`admin` | `comercio` | `cliente`)

## Identidad

Navy + coral + ámbar. Tipografías Syne + Manrope.
