SISTEMA INTEGRAL DE TESORERÍA – IESTP UTCUBAMBA
Versión de desarrollo funcional – 2026

REQUISITOS
- XAMPP (Apache + MySQL/MariaDB)
- PHP 8.1+ con PDO MySQL y Fileinfo habilitados
- MySQL 8 o MariaDB compatible

INSTALACIÓN LOCAL
1. Descomprimir la carpeta tesoreria_iestp_utcubamba dentro de C:\xampp\htdocs\
2. Iniciar Apache y MySQL desde XAMPP.
3. Abrir phpMyAdmin y ejecutar database.sql.
4. Revisar config/database.php y ajustar host, base, usuario y contraseña de MySQL.
5. Abrir http://localhost/tesoreria_iestp_utcubamba/setup.php
6. Crear el primer administrador (contraseña mínima de 10 caracteres).
7. Eliminar setup.php inmediatamente después de crear el administrador.
8. Ingresar desde http://localhost/tesoreria_iestp_utcubamba/login.php

FUNCIONES IMPLEMENTADAS
- Inicio y cierre de sesión con contraseñas hash.
- Dashboard con ingresos y egresos confirmados, saldo neto y cantidad de movimientos.
- Registro de ingresos y egresos en estado PENDIENTE.
- Confirmación de movimientos por administrador/tesorero.
- Anulación justificada por administrador, sin borrar el registro.
- Gestión de comprobantes vinculados a movimientos: Boleta, Factura, Recibo, Voucher, etc.
- Adjuntar PDF/JPG/JPEG/PNG (máximo 8 MB), validar MIME y evitar duplicados por tipo/número.
- Búsqueda de comprobantes por número, tipo, concepto o referencia.
- Administración de usuarios, roles, activación/desactivación y restablecimiento de contraseña.
- Registro de estudiantes con DNI, nombres, dirección y programa de estudio.
- Emisión de recibos correlativos de ingreso a caja, asociados al pago y al estudiante.
- Reportes por periodo en CSV compatible con Excel y formato imprimible para Guardar como PDF (solo administrador).
- Auditoría de creación, confirmación, anulación y operaciones de usuario.
- Consultas preparadas PDO, validación CSRF en formularios de operaciones y escape HTML.

ROLES
- administrador: gestión de usuarios, confirmación y anulación, comprobantes y movimientos.
- tesorero: registrar y confirmar movimientos, registrar comprobantes.
- consulta: consultar dashboard, movimientos y comprobantes.

IMPORTANTE
- Los movimientos PENDIENTES y ANULADOS no afectan los totales del dashboard.
- Solo CONFIRMADO se contabiliza.
- Las evidencias se guardan en uploads/comprobantes con nombre aleatorio. En producción configura el servidor para impedir ejecución de scripts en esa carpeta y limita el acceso según la política institucional.
- Configura HTTPS, copias de seguridad de MySQL y permisos del sistema operativo antes de publicar.
- No subas credenciales reales, archivos de comprobantes, datos personales ni respaldos de producción a un repositorio público de GitHub.
- setup.php debe eliminarse luego de crear el primer administrador.
- Esta versión no reemplaza un sistema contable oficial ni está integrada con SIAF. Antes de operar fondos reales, valida procedimientos, permisos, respaldos y controles con el área responsable.

ESTADO DE ALCANCE
Esta entrega completa los flujos base de movimientos, comprobantes y usuarios. Los módulos avanzados de bancos/conciliación, presupuesto, proveedores y cierres de caja aún requieren desarrollo y validación institucional; las tablas iniciales de caja/cuentas no equivalen a esos procesos completos.


ACTUALIZACIÓN DE ESTA VERSIÓN
- Si ya tienes la base de datos creada, importa MIGRACION_ESTUDIANTES_RECIBOS.sql desde phpMyAdmin.
- Si es una instalación nueva, ejecuta database.sql (ya contiene las tablas nuevas).
- Inicia sesión como administrador para ver el menú Reportes PDF / Excel.
- El reporte Excel se descarga en CSV UTF-8 con separador punto y coma, compatible con Excel.
- Para PDF, pulsa Imprimir / Guardar PDF y selecciona “Guardar como PDF” en el navegador.
- Los recibos emitidos registran un ingreso confirmado en movimientos. Verifica el procedimiento institucional antes de usarlo con dinero real.
- La dirección impresa se basa en el portal institucional: Jr. Las Delicias N.º 380, Bagua Grande, Amazonas.
- Este recibo es un formato interno de ingreso a caja; no se presenta como comprobante electrónico SUNAT ni sustituye una autorización tributaria.

- Historial de pagos por DNI: consulta datos del estudiante, recibos emitidos, estado de cada movimiento y total confirmado.
- La aplicación configura la zona horaria America/Lima para fechas mostradas por PHP.


REPORTES DE TODAS LAS TABLAS (ACTUALIZACIÓN)
El módulo Reportes es exclusivo del rol administrador. Permite seleccionar y exportar las tablas de movimientos, estudiantes, recibos, comprobantes, categorías, cuentas bancarias, cierres de caja, usuarios y auditoría. Las tablas con fecha permiten filtrar por periodo. Excel se descarga como CSV UTF-8 compatible con Excel; PDF se genera desde Imprimir / Guardar PDF en el navegador, en orientación horizontal para tablas anchas. Por seguridad, los reportes de usuarios nunca incluyen contraseñas ni hashes.
