# ENS App - Roadmap

## Modulos para la app
- modulo onboard

Se debe diseñar un formulario dinamico que pregunte en 4 etapas de registro, este sistema de onboard remplaza el registro convencional /register, y las etapas son:

1 Nombres, Apellidos, Celular, Fecha de nacimiento, Correo electronico "unico" con este se inicia sesion, contraseña

2 vista elegir sexo: - botones grandes con los signos de masculino o femenino, subir fotografia -> guardar como base 64 en la tabla 

3 Elegir Esposa/o (campo de busqueda de usuarios con el sexo opuesto, debe buscar en tiempo real, conforme el usuario escriba) al seleccionar la pareja debe habilitar el boton Enviar solicitud (esto sera implementado despues), tambien un boton que diga Aun mi esposa/o no se registra

4 Elegir Equipo (para esta fase di que pronto se podra elegir el secto, la region y la superegion, por ahora continua con la app de ENS) -> registrar y redireccionar a /dashboard 

cada cambio de etapa debe tener animaciones tipo fade in fade out

- modulo notificaciones
- modulo usuarios que permita crear usuarios
- modulo registro, que mantiene un registro de las acciones hechas por los usuarios
- modulo registrar pareja (formulario dinamico con animaciones)
- modulo guia ENS -> IA con RAG de la guia de los equipos
- modulo de asistencia a formación
- modulo de calendario
- modulo base de datos (usuarios)
- modulo eventos
    - formularios de registro
- Modulo cumpleaños / aniversarios
    - enviar notificaciones
- modulo informes

## Notas Sobre el Proceso
Pareja -> Equipo -> Sector -> Región -> Superegión "pais"
- Pareja puede estar en Formación = Debe asistir a las Reuniones de Pilotaje, que son 10 reuniones 1 al mes, una vez finalice el pilotaje, la pareja al finalizar el proceso puede aceptar entrar a los equipos
- Las parejas que terminan y acepten entrar en el movimiento, 1 pareja es elegida para ser pareja responsable de equipo
