import { crearEquipo, listarCategorias } from "../modules/equipos.js";
import { mostrarMensaje } from "./mensajes.js";

const $form = document.querySelector("#form-equipo");
const $selectCategoria = document.querySelector("#categoria_id");
const $btnGuardar = document.querySelector("#btn-guardar");

/**
 * TODO [F3] Formulario de registro de equipos.
 *
 * iniciarFormulario({ alGuardar }):
 *  1. Llene el <select id="categoria_id"> con listarCategorias()
 *     (cree cada <option> con createElement, value = id, texto = nombre).
 *  2. Al enviar el formulario (submit):
 *     - Evite el envío tradicional.
 *     - Limpie los errores anteriores: textContent de [data-error] y aria-invalid de los campos.
 *     - Construya el objeto con FormData. categoria_id y costo_interno deben ir como números.
 *     - Deshabilite #btn-guardar y cambie su texto a "Guardando…" mientras espera.
 *     - Llame a crearEquipo(datos).
 *     - Éxito: limpie el formulario, mostrarMensaje("exito", "Equipo <codigo> registrado.")
 *              y llame a alGuardar().
 *     - 422: por cada campo de error.datos.errors, escriba el PRIMER mensaje en
 *            el <p data-error="<campo>"> y marque el campo con aria-invalid="true".
 *     - Otro error: mostrarMensaje("error", ...).
 *     - Siempre (finally): rehabilite el botón y restaure su texto.
 */
export const iniciarFormulario = ({ alGuardar }) => {
  const cargarCategorias = async () => {
    try {
      const respuesta = await listarCategorias();
      $selectCategoria.querySelectorAll("option:not([value=''])").forEach(($option) => $option.remove());

      for (const categoria of respuesta.data) {
        const $option = document.createElement("option");
        $option.value = categoria.id;
        $option.textContent = categoria.nombre;
        $selectCategoria.append($option);
      }
    } catch (error) {
      mostrarMensaje("error", error.datos?.mensaje ?? error.datos?.message ?? "No fue posible cargar las categorías.");
    }
  };

  const limpiarErrores = () => {
    $form.querySelectorAll("[data-error]").forEach(($error) => ($error.textContent = ""));
    $form.querySelectorAll("[aria-invalid]").forEach(($campo) => $campo.removeAttribute("aria-invalid"));
  };

  $form.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    limpiarErrores();

    const formulario = new FormData($form);
    const datos = Object.fromEntries(formulario.entries());
    datos.categoria_id = Number(datos.categoria_id);
    datos.costo_interno = Number(datos.costo_interno);

    const textoOriginal = $btnGuardar.textContent;
    $btnGuardar.disabled = true;
    $btnGuardar.textContent = "Guardando…";

    try {
      const respuesta = await crearEquipo(datos);
      $form.reset();
      mostrarMensaje("exito", `Equipo ${respuesta.data.codigo} registrado.`);
      alGuardar();
    } catch (error) {
      if (error.status === 422) {
        for (const [campo, mensajes] of Object.entries(error.datos.errors ?? {})) {
          const $mensaje = $form.querySelector(`[data-error="${campo}"]`);
          const $campo = $form.elements.namedItem(campo);

          if ($mensaje) $mensaje.textContent = mensajes[0];
          if ($campo) $campo.setAttribute("aria-invalid", "true");
        }
      } else {
        mostrarMensaje("error", error.datos?.mensaje ?? error.datos?.message ?? "No fue posible registrar el equipo.");
      }
    } finally {
      $btnGuardar.disabled = false;
      $btnGuardar.textContent = textoOriginal;
    }
  });

  cargarCategorias();
};
