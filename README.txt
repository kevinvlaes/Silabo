Repositorio de Sílabos - PHP + MySQL + Bootstrap + MVC

PASOS:
1) Copia la carpeta 'silabus-repo' a htdocs (XAMPP) o www (Laragon).
2) Crea la base de datos importando 'database.sql' en phpMyAdmin.
3) Ve a: http://localhost/silabus-repo/public/seed_users.php para crear los 3 usuarios demo con password hash PHP.
4) Entra a: http://localhost/silabus-repo/public/
   - jefe@demo.com / 123456
   - coordinador@demo.com / 123456
   - docente@demo.com / 123456

Roles:
- Jefe: ve todo + gestiona usuarios (CRUD) desde el botón 'Gestionar usuarios'.
- Coordinador: ve sílabos por carrera (configurado en dashboard.php como 'Ingeniería' a modo de ejemplo).
- Docente: ve y sube solo sus sílabos.

Carpeta de archivos subidos:
- public/uploads/

Listo para pruebas locales.


Repositorio de Sílabos - Consulta pública con filtros

Rutas clave:
- /public/           (login)
- /public/index.php?action=consulta   (Consulta pública con filtros Carrera, Año, Semestre)

BD:
- Campo nuevo en `silabos`: `unidad_didactica` VARCHAR(200).
Si ya tenías la BD creada, ejecuta en phpMyAdmin:
  ALTER TABLE silabos ADD COLUMN unidad_didactica VARCHAR(200) AFTER semestre;

Subida:
- El formulario de subir solicita Unidad Didáctica y semestre I/II.

Consulta:
- Filtra por Carrera (5 programas), Año y Semestre, y permite descargar el archivo.

Usuarios demo:
- Ve a /public/seed_users.php para crear 3 usuarios con pass 123456.
