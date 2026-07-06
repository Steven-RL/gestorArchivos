# 📁 Gestor de Archivos PHP + MySQL

Este proyecto es un sistema web para subir, organizar, visualizar y descargar archivos desde un navegador. Está pensado para funcionar como una pequeña biblioteca o repositorio de documentos en entornos locales, utilizando PHP orientado a objetos, MySQL y una interfaz sencilla y responsive.

## ¿Para qué sirve?

El sistema permite:
- Subir archivos desde una página web.
- Listarlos con búsqueda y paginación.
- Visualizarlos en línea cuando el tipo de archivo lo permite.
- Descargar archivos de forma segura.
- Administrar los archivos mediante un panel protegido por login.

Es ideal para compartir documentos, imágenes o PDFs dentro de un entorno interno o de pruebas sin depender de servicios externos.

---

## ✅ Funcionalidades principales

- Subida de archivos con validación de tipo, extensión y tamaño máximo de 10 MB.
- Visualización directa de archivos compatibles como imágenes, PDF y documentos.
- Descarga segura con control de acceso por token.
- Renombrado lógico del archivo para mostrar un nombre más claro al usuario.
- Eliminación de archivos con confirmación.
- Panel de administración para gestionar contenidos.
- Protección básica contra ataques comunes como inyección SQL, XSS y CSRF.

---

## 📋 Requisitos

Antes de instalarlo asegúrate de tener:
- PHP 8.0 o superior
- MySQL 5.7 o superior
- Apache o cualquier servidor web compatible con PHP
- Extensiones PHP activadas: pdo_mysql, fileinfo y mbstring
- XAMPP, WAMP o Laragon recomendados para probarlo localmente

---

## 🛠️ Instalación paso a paso

1. Coloca el proyecto en la carpeta de tu servidor local.
   - Ejemplo en XAMPP: `C:\xampp\htdocs\gestor_sql`

2. Inicia Apache y MySQL desde XAMPP o el gestor que estés usando.

3. Importa la base de datos.
   - Abre phpMyAdmin.
   - Crea una base de datos llamada `gestor_archivos`.
   - Ve a la pestaña Importar.
   - Selecciona el archivo `sql/gestor_archivos.sql` y haz clic en Importar.

   También puedes importar el SQL desde la línea de comandos si lo prefieres.

4. Configura la conexión a la base de datos.
   - Abre `includes/conexion.php`.
   - Ajusta los valores de host, usuario, contraseña y nombre de la base de datos si es necesario.
   - Por defecto el proyecto usa:
     - host: `localhost`
     - base de datos: `gestor_archivos`
     - usuario: `root`
     - contraseña: vacía

5. Configura el acceso del administrador (opcional).
   - Edita `includes/credenciales_admin.php` si deseas cambiar el usuario o la contraseña.
   - El usuario por defecto es `admin`.
   - La contraseña por defecto es `Admin123*`.

6. Verifica que la carpeta `uploads` exista y tenga permisos de escritura.
   - La carpeta ya viene incluida en el proyecto.
   - Asegúrate de que el servidor pueda crear y guardar archivos ahí.

---

## ▶️ Cómo probarlo localmente

1. Abre tu navegador y entra a:
   - `http://localhost/gestor_sql/`

2. En la página principal podrás ver la lista de archivos disponibles.

3. Para administrar archivos:
   - Ingresa a `http://localhost/gestor_sql/login.php`
   - Inicia sesión con:
     - Usuario: `admin`
     - Contraseña: `Admin123*`

4. Desde el panel de administración podrás:
   - Subir archivos
   - Renombrarlos
   - Eliminarlos

5. Prueba subiendo un PDF, imagen o documento para verificar el flujo completo.

---

## 🧑‍💻 Cómo usar el sistema

### Vista pública
- Cualquier persona puede ver la lista de archivos.
- Puede visualizar o descargar los archivos disponibles.

### Panel de administración
- Requiere iniciar sesión.
- Permite gestionar archivos de forma segura.
- Los archivos se guardan con un nombre interno generado automáticamente y se muestran con un nombre visible más amigable.

---

## 📂 Estructura del proyecto

- `admin/`: páginas del panel de administración
- `clases/`: lógica del sistema en PHP orientado a objetos
- `includes/`: configuración, conexión a MySQL y helpers globales
- `css/`: estilos del proyecto
- `sql/`: script SQL para crear la base de datos
- `uploads/`: carpeta donde se almacenan los archivos subidos

---

## ⚠️ Nota importante

Si al abrir el proyecto aparece un error de conexión a la base de datos, revisa:
- Que Apache y MySQL estén activos
- Que el archivo `sql/gestor_archivos.sql` haya sido importado correctamente
- Que los datos de conexión en `includes/conexion.php` sean correctos
