-- Script para agregar un nuevo tipo de consumible para indicaciones de texto libre
INSERT INTO consumible (nombre, existencia, precio, tipo, marca, codigo, calculo, unidades, presentacion, descripcion) 
VALUES ('Indicación de texto libre', 99999, 0, 'Indicación', 'Sistema', 'TEXTO_LIBRE', 0, 'unidad', 'Texto Libre', 'Permite ingresar indicaciones personalizadas con texto libre.');
