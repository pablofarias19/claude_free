-- ═══════════════════════════════════════════════════════════════════
-- SEED: Pablo Farias Abogados — Trámites Sucesorios
-- Base: knowledge-base.md (Derecho Sucesorio Argentino e Internacional)
-- WhatsApp contacto: +54 9 11 6848-0793
-- ═══════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Limpiar datos anteriores
TRUNCATE TABLE chat_bot_responses;
TRUNCATE TABLE chat_canned;

-- ───────────────────────────────────────────────────────────────────
-- BOT RESPONSES
-- Formato: trigger_word | response_text | quick_replies JSON | categoria | prioridad
-- ───────────────────────────────────────────────────────────────────

INSERT INTO chat_bot_responses
  (trigger_word, response_text, quick_replies, is_exact_match, priority, is_active, category)
VALUES

-- ════════════════════════════════════════
-- CATEGORÍA: Bienvenida
-- ════════════════════════════════════════

('hola',
'¡Hola! Bienvenido al estudio **Pablo Farias Abogados**, especialistas en trámites sucesorios. Atendemos en todo el país y brindamos la primera consulta sin cargo. ¿En qué te puedo orientar hoy?',
'[{"text":"¿Qué es una sucesión?","value":"que es sucesion"},{"text":"¿Cuánto cuesta?","value":"honorarios"},{"text":"¿Qué documentos necesito?","value":"documentos"},{"text":"Hablar con el estudio","value":"contacto"}]',
0, 10, 1, 'Bienvenida'),

('buenos días',
'¡Buenos días! Bienvenido al estudio **Pablo Farias Abogados**. Somos especialistas en derecho sucesorio argentino e internacional. La primera consulta es sin cargo. ¿Cómo podemos ayudarte?',
'[{"text":"Iniciar sucesión","value":"como empezar"},{"text":"Costos y honorarios","value":"honorarios"},{"text":"WhatsApp directo","value":"whatsapp"}]',
0, 9, 1, 'Bienvenida'),

('buenas tardes',
'¡Buenas tardes! Soy el asistente virtual del estudio **Pablo Farias Abogados**. Podés preguntarme sobre el proceso sucesorio, documentación, costos o sucesiones internacionales. ¿Por dónde empezamos?',
'[{"text":"¿Cómo inicio la sucesión?","value":"como empezar"},{"text":"¿Cuánto tiempo demora?","value":"tiempo"},{"text":"Hablar con el estudio","value":"contacto"}]',
0, 9, 1, 'Bienvenida'),

('consulta',
'Con gusto te oriento. El estudio **Pablo Farias Abogados** ofrece la primera consulta sin cargo, en persona o por videollamada. Podés también escribirnos por WhatsApp al **+54 9 11 6848-0793**. ¿Qué necesitás saber?',
'[{"text":"Proceso sucesorio","value":"proceso"},{"text":"Documentos necesarios","value":"documentos"},{"text":"Costos","value":"honorarios"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 8, 1, 'Bienvenida'),

-- ════════════════════════════════════════
-- CATEGORÍA: Proceso Sucesorio
-- ════════════════════════════════════════

('que es una sucesion',
'La **sucesión** es el proceso legal por el cual se transmiten los bienes, derechos y obligaciones de una persona fallecida (el "causante") a sus herederos. Comprende tanto el activo (bienes y créditos) como el pasivo (deudas). La transmisión ocurre de pleno derecho al momento del fallecimiento, aunque el proceso judicial se inicia después. ¿Querés saber cómo empezar?',
'[{"text":"¿Cómo se inicia?","value":"como empezar"},{"text":"¿Cuánto demora?","value":"tiempo"},{"text":"¿Qué necesito?","value":"documentos"}]',
0, 9, 1, 'Proceso'),

('como empezar',
'Para iniciar la sucesión necesitás presentarte ante el juzgado del **último domicilio del causante** con: acta de defunción, DNI del fallecido y documentos que acrediten el vínculo familiar (actas de nacimiento/matrimonio). El estudio se encarga de todo el trámite. Te recomendamos contactarnos para una evaluación inicial sin cargo.',
'[{"text":"¿Qué documentos exactos?","value":"documentos"},{"text":"¿Cuánto cuesta?","value":"honorarios"},{"text":"Contactar al estudio","value":"contacto"}]',
0, 9, 1, 'Proceso'),

('como iniciar',
'El proceso sucesorio se inicia con la **apertura** ante el juzgado del último domicilio del causante. El estudio realiza: presentación judicial, publicación de edictos, obtención de la declaratoria de herederos, inventario y partición de bienes. La primera consulta es sin cargo. ¿Te llamo o preferís WhatsApp?',
'[{"text":"Ver etapas completas","value":"etapas"},{"text":"Documentos necesarios","value":"documentos"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 9, 1, 'Proceso'),

('etapas',
'El proceso sucesorio tiene **6 etapas principales**:\n\n1️⃣ **Apertura** (0-30 días): presentación judicial, medidas cautelares\n2️⃣ **Edictos** (30-60 días): publicación en Boletín Oficial convocando a herederos y acreedores\n3️⃣ **Declaratoria** (60-120 días): el juez reconoce formalmente a los herederos\n4️⃣ **Inventario y avalúo** (variable): descripción y tasación de todos los bienes\n5️⃣ **Partición** (variable): división y adjudicación entre herederos\n6️⃣ **Inscripciones**: transferencia registral de inmuebles, vehículos, cuentas\n\nEl plazo total depende de la complejidad y jurisdicción.',
'[{"text":"¿Cuánto tiempo en total?","value":"tiempo"},{"text":"¿Cuánto cuesta?","value":"honorarios"},{"text":"Iniciar ahora","value":"contacto"}]',
0, 8, 1, 'Proceso'),

('cuanto tiempo',
'Los plazos **orientativos** son:\n\n• Sucesión simple (un heredero, un inmueble): **6 a 12 meses**\n• Sucesión media (varios herederos, varios bienes): **12 a 24 meses**\n• Sucesión compleja (conflictos, bienes en varias provincias o exterior): **2 a 4 años**\n\nEn CABA y PBA los juzgados están más cargados; en provincias suele ser más rápido. ¿Querés una estimación para tu caso específico?',
'[{"text":"Consultar mi caso","value":"contacto"},{"text":"¿Qué documentos necesito?","value":"documentos"},{"text":"¿Cuánto cuesta?","value":"honorarios"}]',
0, 8, 1, 'Proceso'),

('demora',
'El tiempo varía según la complejidad:\n\n• **Sucesión simple**: 6 a 12 meses\n• **Con varios herederos o bienes**: 12 a 24 meses\n• **Internacional o conflictiva**: más de 2 años\n\nEl estudio trabaja activamente para acelerar cada etapa. Te podemos dar una estimación más precisa conociendo tu caso. ¿Consultamos sin cargo?',
'[{"text":"Consultar ahora","value":"contacto"},{"text":"Ver las etapas","value":"etapas"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 8, 1, 'Proceso'),

('declaratoria de herederos',
'La **declaratoria de herederos** es la resolución judicial que reconoce formalmente quiénes son los herederos del causante. Es el documento clave para luego poder transferir bienes registrables (inmuebles, vehículos, cuentas). Aclararón: la calidad de heredero nace con la muerte, no con la declaratoria; pero sin ella no se puede inscribir ningún bien.',
'[{"text":"¿Cuánto tarda?","value":"tiempo"},{"text":"¿Qué documentos necesito?","value":"documentos"},{"text":"Iniciar trámite","value":"contacto"}]',
0, 8, 1, 'Proceso'),

('sin testamento',
'Cuando no hay testamento la sucesión se llama **intestada** o *ab intestato*. La ley establece el orden de herederos:\n\n1° Los **descendientes** (hijos, nietos)\n2° Los **ascendientes** (padres, abuelos) — si no hay hijos\n3° El **cónyuge supérstite** — concurre con los anteriores\n4° **Colaterales** (hermanos, sobrinos, tíos) hasta 4° grado\n\nEste orden determina quién hereda y en qué proporción. ¿Querés saber cómo aplica en tu caso?',
'[{"text":"Consultar mi situación","value":"contacto"},{"text":"¿Cuánto cuesta?","value":"honorarios"},{"text":"¿Qué documentos?","value":"documentos"}]',
0, 8, 1, 'Proceso'),

('herederos',
'Los **herederos** son quienes reciben el patrimonio del causante. En Argentina hay dos tipos:\n\n• **Herederos forzosos**: hijos, padres, cónyuge. La ley les reserva una porción mínima (legítima) que no puede quitárseles ni por testamento.\n• **Herederos voluntarios**: designados por testamento dentro de la porción disponible.\n\nSi hay varios herederos, el proceso requiere acuerdo para la partición. ¿Hay alguna situación particular en tu caso?',
'[{"text":"¿Cuál es mi porción?","value":"legitima"},{"text":"Hay conflicto entre herederos","value":"conflicto"},{"text":"Consultar","value":"contacto"}]',
0, 8, 1, 'Proceso'),

-- ════════════════════════════════════════
-- CATEGORÍA: Documentación
-- ════════════════════════════════════════

('documentos',
'Los documentos básicos que necesitás son:\n\n**Del causante:**\n• Acta de defunción original\n• DNI (aunque no lo tengas, se puede obtener)\n• Actas de estado civil (matrimonio, divorcio)\n\n**De los herederos:**\n• DNI de cada heredero\n• Actas de nacimiento (para acreditar el vínculo)\n\n**De los bienes:**\n• Escritura de inmuebles, títulos de vehículos, etc.\n\nNo es necesario tener todo desde el inicio. El estudio te guía paso a paso. ¿Hay algún bien específico?',
'[{"text":"Documentos para inmuebles","value":"documentos inmueble"},{"text":"Documentos para auto","value":"documentos vehiculo"},{"text":"Documentos para cuentas","value":"documentos banco"},{"text":"Consultar","value":"contacto"}]',
0, 9, 1, 'Documentación'),

('acta de defuncion',
'El **acta de defunción** es el primer documento que necesitás. Se obtiene en el Registro Civil del lugar donde ocurrió el fallecimiento. Si fue en el extranjero, necesita Apostilla de La Haya y traducción jurada. Es el documento que inicia todo el proceso sucesorio.',
'[{"text":"¿Qué más necesito?","value":"documentos"},{"text":"¿Cómo inicio el trámite?","value":"como empezar"},{"text":"Contactar","value":"contacto"}]',
0, 7, 1, 'Documentación'),

('documentos inmueble',
'Para los **inmuebles** necesitás:\n\n• Escritura de propiedad (o datos del Registro de la Propiedad Inmueble)\n• Último pago de impuestos (ABL, inmobiliario provincial)\n• Plano de mensura (si existe)\n• Certificado de dominio del Registro\n• Inhibiciones del causante (lo obtiene el abogado)\n\nSi no tenés la escritura, se puede obtener copia en el Registro. El estudio se encarga de todos estos trámites.',
'[{"text":"¿Cuánto cuesta inscribir?","value":"honorarios"},{"text":"Consultar mi caso","value":"contacto"}]',
0, 7, 1, 'Documentación'),

('documentos vehiculo',
'Para los **vehículos** (autos, motos, camiones) necesitás:\n\n• Título del automotor (tarjeta verde)\n• Cédula de identificación\n• Verificación policial vigente\n• Libre deuda de patentes\n\nLos vehículos se transfieren en el **Registro Seccional del Automotor** correspondiente. El trámite post-declaratoria es relativamente rápido.',
'[{"text":"¿Cuánto demora la transferencia?","value":"tiempo"},{"text":"Consultar","value":"contacto"}]',
0, 7, 1, 'Documentación'),

('documentos banco',
'Para las **cuentas bancarias y activos financieros** necesitás:\n\n• Declaratoria de herederos (obligatoria)\n• DNI de herederos\n• Poderes notariales si actúa un representante\n• En algunos bancos: acta de designación de administrador\n\nCada banco tiene su propio procedimiento interno. El estudio coordina con las entidades financieras para el desbloqueo y transferencia.',
'[{"text":"¿Y las criptomonedas?","value":"crypto"},{"text":"Consultar mi caso","value":"contacto"}]',
0, 7, 1, 'Documentación'),

-- ════════════════════════════════════════
-- CATEGORÍA: Honorarios
-- ════════════════════════════════════════

('honorarios',
'El estudio trabaja con **presupuesto por etapas**:\n\n1. **Consulta inicial**: sin cargo\n2. **Apertura y declaratoria**: honorario fijo según complejidad\n3. **Inscripciones registrales**: honorario por gestión\n4. **Partición**: porcentaje sobre el valor de lo partido\n\n**Modalidades de pago**: contado, cuotas sin interés (hasta 12), o por hito procesal alcanzado.\n\nAdicionalmente hay costos judiciales (tasa de justicia: 3% en CABA, 1,5% en PBA del activo bruto). ¿Querés una estimación para tu caso?',
'[{"text":"Solicitar presupuesto","value":"contacto"},{"text":"¿Qué es la tasa de justicia?","value":"tasa justicia"},{"text":"¿Hay impuestos?","value":"impuestos"}]',
0, 9, 1, 'Honorarios'),

('costo',
'Los **costos** de una sucesión tienen dos componentes:\n\n**Gastos judiciales (obligatorios)**:\n• Tasa de justicia: 3% del activo en CABA / 1,5% en PBA\n• Tasa de actuación notarial: 2-4% del valor de escrituración\n\n**Honorarios del abogado**:\n• Regulados judicialmente al cierre: aprox. 8-15% del activo neto\n• El estudio ofrece presupuesto fijo por etapas y facilidades de pago\n\nLa primera consulta es **sin cargo**. ¿Te hacemos un presupuesto orientativo?',
'[{"text":"Pedir presupuesto","value":"contacto"},{"text":"¿Puedo pagar en cuotas?","value":"cuotas"},{"text":"¿Hay impuestos?","value":"impuestos"}]',
0, 9, 1, 'Honorarios'),

('cuanto cuesta',
'El costo depende del valor del acervo (total de bienes) y la jurisdicción. **Orientativamente**: para una sucesión con un inmueble de $100.000 USD en CABA, los honorarios totales (abogado + gastos) suelen rondar el 12-18% del valor. El estudio ofrece **facilidades de pago** y la primera consulta es sin cargo. Hacemos un presupuesto sin compromiso.',
'[{"text":"Solicitar presupuesto","value":"contacto"},{"text":"¿Puedo pagar en cuotas?","value":"cuotas"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 9, 1, 'Honorarios'),

('cuotas',
'Sí, el estudio ofrece **facilidades de pago**:\n\n• Hasta **12 cuotas sin interés**\n• Pago **contra hito procesal** (pagás cuando se alcanza cada etapa)\n• **Pago al cierre** en casos que lo permitan\n\nEntendemos que la sucesión ya implica un momento difícil. Por eso buscamos la modalidad que mejor se adapte a cada familia. ¿Coordinamos una consulta?',
'[{"text":"Contactar ahora","value":"contacto"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 8, 1, 'Honorarios'),

('impuestos',
'**Argentina no tiene impuesto nacional a la herencia**. Los costos fiscales son:\n\n• **Tasa de justicia**: 3% en CABA / 1,5% en PBA (sobre el activo)\n• **ITI** (Impuesto a la Transferencia de Inmuebles): 1,5% — solo si el heredero NO es familiar directo\n• **Impuesto de sellos** provincial en las escrituras\n\n⚠️ En **sucesiones internacionales** (España, Italia, etc.) los impuestos extranjeros pueden ser significativos. Consultanos para ese caso.',
'[{"text":"Sucesión internacional","value":"internacional"},{"text":"¿Cuánto cuesta todo?","value":"costo"},{"text":"Consultar","value":"contacto"}]',
0, 8, 1, 'Honorarios'),

('tasa de justicia',
'La **tasa de justicia** es un arancel que cobra el juzgado al inicio del proceso. Se calcula sobre el valor del acervo hereditario (total de bienes):\n\n• **CABA**: 3% del activo\n• **Buenos Aires (PBA)**: 1,5% del activo bruto declarado\n• **Córdoba**: escala según valor (Ley 9.459)\n• **Entre Ríos**: escala sobre activo neto (Ley 7.046)\n\nNo confundir con los honorarios del abogado, que son un concepto separado.',
'[{"text":"¿Y los honorarios?","value":"honorarios"},{"text":"Consultar presupuesto","value":"contacto"}]',
0, 7, 1, 'Honorarios'),

-- ════════════════════════════════════════
-- CATEGORÍA: Testamento
-- ════════════════════════════════════════

('testamento',
'En Argentina existen **3 tipos de testamento** válidos:\n\n1. **Ológrafo** (art. 2477 CCyCN): escrito, fechado y firmado totalmente a mano por el testador. No requiere escribano. Es el más simple pero más vulnerable a impugnaciones.\n\n2. **Por acto público** (art. 2479): ante escribano y dos testigos. Es el más seguro y recomendado.\n\n3. **Cerrado** (art. 2481): entregado en sobre sellado ante escribano. Poco usado actualmente.\n\n⚠️ El testamento **siempre debe respetar la legítima** de los herederos forzosos.',
'[{"text":"¿Qué es la legítima?","value":"legitima"},{"text":"¿Puedo hacer mi testamento?","value":"contacto"},{"text":"¿Qué puedo legar?","value":"legado"}]',
0, 8, 1, 'Testamento'),

('hay testamento',
'Cuando existe testamento la sucesión es **testamentaria**. El juez primero verifica su validez formal y lo protocoliza, luego aplica sus disposiciones respetando la legítima de los herederos forzosos. Si el testamento invade la legítima, los herederos afectados pueden ejercer la **acción de reducción**. ¿Querés que analizemos el testamento de tu caso?',
'[{"text":"¿Qué es la acción de reducción?","value":"accion de reduccion"},{"text":"¿Qué es la legítima?","value":"legitima"},{"text":"Consultar","value":"contacto"}]',
0, 8, 1, 'Testamento'),

('legado',
'El **legatario** recibe un bien determinado por testamento (no una parte del patrimonio total). A diferencia del heredero:\n\n• Recibe un bien específico: un inmueble, auto, suma de dinero, etc.\n• No tiene "vocación al todo" (no se expande si otro renuncia)\n• Responde por deudas solo hasta el valor del legado recibido\n• Su patrimonio personal siempre está protegido\n\nEl testador puede legar libremente dentro de la **porción disponible** (1/3 si hay hijos, 1/2 si solo hay padres o cónyuge).',
'[{"text":"¿Qué es la porción disponible?","value":"legitima"},{"text":"Consultar mi caso","value":"contacto"}]',
0, 7, 1, 'Testamento'),

('accion de reduccion',
'La **acción de reducción** (art. 2452 CCyCN) permite a los herederos forzosos atacar los legados o donaciones que invadan su porción legítima. Si el testador legó más de lo que podía, esos legados "inoficiosos" se reducen para proteger la legítima. Es una acción judicial que puede ejercerse después del fallecimiento del testador.',
'[{"text":"¿Cuál es mi legítima?","value":"legitima"},{"text":"Tengo un conflicto","value":"conflicto"},{"text":"Consultar","value":"contacto"}]',
0, 7, 1, 'Testamento'),

-- ════════════════════════════════════════
-- CATEGORÍA: Herencia / Legítima
-- ════════════════════════════════════════

('legitima',
'La **legítima** es la porción mínima del patrimonio que la ley reserva a los herederos forzosos, de la que no pueden ser privados (art. 2444 CCyCN):\n\n• **Descendientes** (hijos, nietos): **2/3** del patrimonio neto\n• **Ascendientes** (padres, abuelos): **1/2** del patrimonio neto\n• **Cónyuge**: **1/2** del patrimonio neto\n\nEl testador puede disponer libremente solo de la **porción disponible** (lo que resta). Por ejemplo, si hay hijos, solo puede donar o legar el 1/3 restante.',
'[{"text":"¿Me quitaron mi legítima?","value":"accion de reduccion"},{"text":"¿Qué es el testamento?","value":"testamento"},{"text":"Consultar","value":"contacto"}]',
0, 8, 1, 'Herencia'),

('heredero forzoso',
'Los **herederos forzosos** (o legitimarios) son aquellos que la ley protege con una porción intocable de la herencia:\n\n• **Descendientes**: hijos, nietos (excluyen a ascendientes)\n• **Ascendientes**: padres, abuelos (a falta de descendientes)\n• **Cónyuge supérstite**: concurre con los anteriores\n\nSon herederos desde el momento del fallecimiento (investidura de pleno derecho). Responden por deudas hasta el valor de lo heredado, no con su patrimonio personal.',
'[{"text":"¿Cuál es mi legítima?","value":"legitima"},{"text":"¿Y si hay testamento?","value":"testamento"},{"text":"Consultar","value":"contacto"}]',
0, 8, 1, 'Herencia'),

('renunciar herencia',
'Sí, se puede **renunciar a la herencia**. La renuncia debe ser:\n\n• **Expresa** (no se presume)\n• Realizada ante **escribano público** o en el **expediente judicial**\n• Art. 2299 CCyCN\n\nEl renunciante es tratado como si nunca hubiera sido heredero. Su parte acrece a los demás coherederos. ⚠️ La renuncia es **irrevocable** una vez aceptada judicialmente. Antes de renunciar, conviene analizar si hay deudas que superen los bienes.',
'[{"text":"¿Por qué renunciarían?","value":"deudas"},{"text":"Consultar mi caso","value":"contacto"}]',
0, 8, 1, 'Herencia'),

('aceptar herencia',
'La **aceptación** puede ser:\n\n• **Expresa**: declaración formal ante el juzgado\n• **Tácita**: cuando el heredero realiza actos que suponen aceptación (vende bienes, paga deudas de la sucesión, etc.)\n\nDesde agosto 2015 (CCyCN), todo heredero goza automáticamente del **beneficio de inventario**: responde por las deudas solo hasta el valor de lo heredado. Ya no hay riesgo de heredar más deudas que bienes.',
'[{"text":"¿Qué pasa con las deudas?","value":"deudas"},{"text":"¿Cuándo debo aceptar?","value":"plazo aceptacion"},{"text":"Consultar","value":"contacto"}]',
0, 8, 1, 'Herencia'),

('deudas',
'Los herederos **NO responden con su patrimonio personal** por las deudas del causante. El art. 2317 CCyCN establece el **beneficio de inventario automático**: cada heredero responde solo hasta el valor de los bienes que recibe. Si las deudas superan el activo, simplemente no heredan nada pero tampoco pierden lo propio.',
'[{"text":"¿Conviene aceptar o renunciar?","value":"renunciar herencia"},{"text":"Consultar mi caso","value":"contacto"}]',
0, 8, 1, 'Herencia'),

('colacion',
'La **colación** (art. 2496 CCyCN) obliga a los descendientes que concurren a la sucesión a sumar a la masa hereditaria los bienes recibidos en vida del causante (donaciones). Esto garantiza la igualdad entre herederos. Solo aplica entre descendientes, no entre hermanos. El heredero puede renunciar a la herencia para no colacionar, pero no puede heredar sin colacionar.',
'[{"text":"Consultar mi situación","value":"contacto"},{"text":"¿Qué es la legítima?","value":"legitima"}]',
0, 7, 1, 'Herencia'),

('conflicto',
'Cuando los herederos no logran un acuerdo, hay varias vías:\n\n1. **Mediación**: el estudio trabaja activamente para evitar el proceso litigioso\n2. **Partición judicial**: el juez ordena la división; puede incluir subasta pública\n3. **Cesión de derechos hereditarios** (art. 2302 CCyCN): un heredero puede vender su porción a otro coheredero o a un tercero — requiere escritura pública\n\nLa subasta pública es la solución más costosa y lenta. Siempre conviene buscar acuerdo. ¿Querés que mediemos?',
'[{"text":"Hablar con el abogado","value":"contacto"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 8, 1, 'Herencia'),

('heredero vender',
'Sí. Un heredero puede **ceder sus derechos hereditarios** antes de que termine la sucesión (art. 2302 CCyCN). Puede ceder a otro coheredero o a un tercero. Requiere escritura pública. El cesionario ocupa el lugar del cedente en el proceso. Es una salida habitual cuando un heredero necesita liquidez rápida.',
'[{"text":"¿Cómo se hace?","value":"contacto"},{"text":"¿Tiene costo?","value":"honorarios"}]',
0, 7, 1, 'Herencia'),

-- ════════════════════════════════════════
-- CATEGORÍA: Bienes específicos
-- ════════════════════════════════════════

('inmueble',
'Los **inmuebles** (casas, departamentos, terrenos, campos) son el bien más común en las sucesiones. Para poder inscribir la transferencia en el Registro de la Propiedad Inmueble se necesita:\n\n• Declaratoria de herederos firme\n• Acuerdo de partición entre herederos\n• Escritura pública ante escribano\n• Pago de tasa de justicia e impuestos correspondientes\n\nSin la declaratoria NO se puede vender ni hipotecar un inmueble heredado.',
'[{"text":"¿Qué documentos necesito?","value":"documentos inmueble"},{"text":"¿Cuánto cuesta?","value":"honorarios"},{"text":"Consultar","value":"contacto"}]',
0, 8, 1, 'Bienes'),

('auto',
'Los **vehículos** (autos, motos, camiones, maquinaria) se transfieren a través del **Registro Seccional del Automotor** correspondiente a la radicación del vehículo. Necesitás la declaratoria de herederos y acuerdo entre herederos. Es un trámite relativamente rápido una vez obtenida la declaratoria. ¿Hay vehículos en la sucesión?',
'[{"text":"¿Qué documentos?","value":"documentos vehiculo"},{"text":"¿Cuánto cuesta?","value":"honorarios"},{"text":"Consultar","value":"contacto"}]',
0, 7, 1, 'Bienes'),

('cuenta bancaria',
'Las **cuentas bancarias** se desbloquean con la declaratoria de herederos. Cada banco tiene su procedimiento interno pero en general exige: declaratoria firme, DNI de herederos y nota de solicitud. Los **plazos fijos** y **fondos comunes** se resuelven de la misma manera. ¿Sabés en qué banco?',
'[{"text":"¿Y si hay criptomonedas?","value":"crypto"},{"text":"Consultar","value":"contacto"}]',
0, 7, 1, 'Bienes'),

('crypto',
'Las **criptomonedas y activos digitales** forman parte del acervo hereditario (art. 16 CCyCN — bienes económicos). Son parte de la sucesión. Los pasos son:\n\n1. Identificar las billeteras (wallets), exchanges y accesos\n2. Incluirlos en el inventario con su valuación al momento\n3. Coordinar la transferencia con el exchange (Lemon, Binance, etc.)\n\nEl estudio tiene experiencia en **inventario de activos digitales**. Es fundamental actuar rápido ya que las claves pueden perderse.',
'[{"text":"Consultar urgente","value":"contacto"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 7, 1, 'Bienes'),

('empresa',
'Si el causante era **socio o accionista** de una empresa, las cuotas o acciones también se heredan. Para ello:\n\n• **SRL**: transferencia de cuotas societarias según estatuto y Ley 19.550\n• **SA**: transferencia de acciones en libro de registro o mediante CAVALI (si cotiza)\n• Se requiere valuación contable o de mercado\n• Puede requerir modificación del contrato social\n\nEl estudio asesora en la **planificación sucesoria empresarial** para evitar conflictos futuros.',
'[{"text":"Planificación preventiva","value":"planificacion"},{"text":"Consultar","value":"contacto"}]',
0, 7, 1, 'Bienes'),

-- ════════════════════════════════════════
-- CATEGORÍA: Internacional
-- ════════════════════════════════════════

('internacional',
'El estudio está especializado en **sucesiones internacionales**. La regla general es que la sucesión se rige por la ley del **último domicilio del causante** (art. 2644 CCyCN), pero los **inmuebles en Argentina** siempre se rigen por ley argentina. Trabajamos con Argentina, España, Italia, Grecia, Uruguay y Brasil, con red de abogados corresponsales.',
'[{"text":"España","value":"españa"},{"text":"Italia","value":"italia"},{"text":"Grecia","value":"grecia"},{"text":"Uruguay o Brasil","value":"uruguay brasil"}]',
0, 9, 1, 'Internacional'),

('españa',
'Para sucesiones con bienes o herederos en **España**:\n\n• Aplica el **Reglamento UE 650/2012**: los ciudadanos europeos pueden elegir la ley de su nacionalidad\n• El **Certificado Sucesorio Europeo** es reconocido en todos los países de la UE\n• Se requiere **Apostilla de La Haya** para documentos que cruzan fronteras\n• Cada comunidad autónoma tiene su propio Impuesto de Sucesiones (ISD) — diferencias significativas entre Madrid, Cataluña, Andalucía\n• El estudio coordina con abogado/notario en España\n\n¿Hay inmuebles, cuentas o ciudadanía española involucrada?',
'[{"text":"Consultar mi caso","value":"contacto"},{"text":"¿Cuánto cuesta?","value":"honorarios"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 8, 1, 'Internacional'),

('italia',
'Para sucesiones con bienes o herederos en **Italia**:\n\n• El Codice Civile arts. 456-809 rige las sucesiones italianas\n• Aplica el Reglamento europeo 650/2012 desde 2015\n• Bienes muebles: ley del domicilio del causante / Inmuebles: ley del lugar (lex situs)\n• Se requiere Apostilla y traducción jurada al italiano\n• **Ciudadanía italiana por descendencia**: frecuentemente vinculada a herencias\n• Coordinación con *notaio* o *avvocato* en Italia\n\n¿Hay inmuebles, cuentas o ciudadanía italiana en juego?',
'[{"text":"Consultar mi caso","value":"contacto"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 8, 1, 'Internacional'),

('grecia',
'Para sucesiones con bienes o herederos en **Grecia**:\n\n• Aplica el Reglamento europeo 650/2012 desde 2015\n• Particularidad: muchos inmuebles griegos tienen titularidad confusa por falta de catastro actualizado\n• El Ktimatológio (catastro griego) está en proceso de digitalización — puede requerir verificación in situ\n• Documentos requieren Apostilla de La Haya\n• El estudio coordina con *dikigóros* (abogado griego) corresponsal\n\n¿Tenés propiedades o familiares en Grecia?',
'[{"text":"Consultar mi caso","value":"contacto"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 8, 1, 'Internacional'),

('uruguay brasil',
'Sucesiones en **Uruguay**:\n• Impuesto a las Transmisiones Patrimoniales (ITP): 3% del valor de los bienes\n• El proceso se puede realizar extrajudicialmente si hay acuerdo\n• Es posible homologar la declaratoria argentina en Uruguay\n\nSucesiones en **Brasil**:\n• Inventário extrajudicial si hay acuerdo (ante cartório)\n• ITCMD (impuesto causa mortis): varía por estado (4% a 8%)\n• Los herederos extranjeros deben obtener CPF (registro fiscal)\n\nEl estudio coordina con corresponsales en ambos países.',
'[{"text":"Consultar mi caso","value":"contacto"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 8, 1, 'Internacional'),

('apostilla',
'La **Apostilla de La Haya** es una certificación que hace válido un documento oficial en otro país signatario del Convenio. Argentina adhirió en 1987. Todos los documentos que cruzan fronteras en sucesiones internacionales la necesitan: partidas de nacimiento, defunción, matrimonio, poderes, declaratorias, etc. El apostillado se realiza en Argentina ante el Ministerio de Relaciones Exteriores o el Colegio de Escribanos.',
'[{"text":"Sucesión internacional","value":"internacional"},{"text":"Consultar","value":"contacto"}]',
0, 7, 1, 'Internacional'),

-- ════════════════════════════════════════
-- CATEGORÍA: Planificación Preventiva
-- ════════════════════════════════════════

('planificacion',
'La **planificación sucesoria preventiva** permite organizar el futuro de tu patrimonio en vida. Las herramientas disponibles son:\n\n1. **Testamento**: distribución clara de bienes respetando la legítima\n2. **Fideicomiso testamentario**: ideal para empresa familiar o beneficiarios con necesidades especiales\n3. **Protocolo familiar**: acuerdo entre socios/familiares para la empresa\n4. **Donación con reserva de usufructo**: transferís el bien pero conservás el uso\n5. **Seguro de vida**: el capital no integra la herencia — liquidación rápida\n\n¿Querés asesoramiento preventivo para tu patrimonio?',
'[{"text":"Consultar ahora","value":"contacto"},{"text":"¿Qué es el fideicomiso?","value":"fideicomiso"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 7, 1, 'Planificación'),

('fideicomiso',
'El **fideicomiso testamentario** (art. 1699 CCyCN) permite que al fallecer, ciertos bienes pasen a un fideicomiso administrado por un fiduciario según tus instrucciones. Sus ventajas:\n\n• Continuidad de la empresa familiar sin paralizar el proceso sucesorio\n• Protección de beneficiarios con incapacidad o menores\n• Distribución escalonada (evita el "golpe de herencia")\n• No puede durar más de 30 años (salvo beneficiario incapaz)\n\nEs una herramienta de planificación sofisticada. El estudio puede asesorarte en su estructuración.',
'[{"text":"Consultar fideicomiso","value":"contacto"},{"text":"Ver otras herramientas","value":"planificacion"}]',
0, 7, 1, 'Planificación'),

-- ════════════════════════════════════════
-- CATEGORÍA: Situaciones especiales
-- ════════════════════════════════════════

('herencia vacante',
'La **herencia vacante** ocurre cuando no hay herederos o todos renuncian. Los bienes pasan al **Estado** (Nacional, Provincial o CABA según ubicación). El Estado no hereda como sucesor sino por dominio eminente, y responde por deudas solo hasta el valor recibido. Un juez designa un curador que inventaria, paga deudas y entrega el remanente al Estado. Si aparece un heredero después, puede reclamar los bienes en el estado que se encuentren.',
'[{"text":"¿Puedo reclamar?","value":"contacto"},{"text":"Consultar","value":"contacto"}]',
0, 7, 1, 'Especial'),

('heredero aparente',
'El **heredero aparente** es quien ejerció derechos hereditarios sin tener derecho, o siendo heredero con exclusión de otro. Sus actos frente a terceros de buena fe son válidos (art. 2315 CCyCN). El heredero real puede reclamar el valor de los bienes al aparente, pero no siempre puede recuperarlos de los terceros que ya los adquirieron. Consultanos si creés que hay un heredero aparente en tu caso.',
'[{"text":"Consultar mi situación","value":"contacto"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 7, 1, 'Especial'),

('bienes digitales',
'Los **bienes digitales** son parte del acervo hereditario (criptomonedas, NFTs, cuentas de exchanges, billeteras digitales, dominios web). Integran la sucesión aunque no estén registrados físicamente. Es vital actuar rápido para no perder las claves de acceso. El estudio tiene experiencia en el inventario y transferencia de activos digitales.',
'[{"text":"Consultar urgente","value":"contacto"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 7, 1, 'Especial'),

('urgente',
'Entendemos la urgencia. Algunas situaciones requieren **medidas cautelares inmediatas**: riesgo de deterioro de bienes, conflictos entre herederos, bienes que se pueden dilapidar. El estudio puede solicitar al juez medidas de protección desde el inicio del proceso. Contactanos hoy por WhatsApp al **+54 9 11 6848-0793** para una respuesta rápida.',
'[{"text":"WhatsApp ahora","value":"whatsapp"},{"text":"Ver número","value":"contacto"}]',
0, 10, 1, 'Especial'),

-- ════════════════════════════════════════
-- CATEGORÍA: Contacto
-- ════════════════════════════════════════

('contacto',
'Para comunicarte con el estudio **Pablo Farias Abogados**:\n\n📱 **WhatsApp**: +54 9 11 6848-0793\n🌐 **Web**: pablofarias.com.ar\n📧 Formulario de contacto en el sitio\n\n✅ **Primera consulta sin cargo**, en persona o por videollamada\n✅ Atención en todo el país\n\n¿Preferís que te escribamos nosotros?',
'[{"text":"WhatsApp","value":"whatsapp"},{"text":"¿Cuándo atienden?","value":"horarios"}]',
0, 10, 1, 'Contacto'),

('whatsapp',
'Podés escribirnos directamente por WhatsApp al **+54 9 11 6848-0793**. Respondemos dentro del horario de atención (lun-vie 9 a 18 hs). Para consultas urgentes mencioná la situación en el primer mensaje así priorizamos la respuesta.',
'[{"text":"¿Primera consulta es gratis?","value":"consulta gratis"},{"text":"¿Qué información preparo?","value":"documentos"}]',
0, 10, 1, 'Contacto'),

('consulta gratis',
'Sí. La **primera consulta es sin cargo**, ya sea en persona, por videollamada o por WhatsApp. En esa primera reunión analizamos tu situación, te explicamos los pasos a seguir y te damos un presupuesto orientativo sin compromiso.',
'[{"text":"Reservar consulta","value":"contacto"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 10, 1, 'Contacto'),

('horarios',
'El estudio atiende **lunes a viernes de 9:00 a 18:00 hs**. Podés:\n\n• Escribir por WhatsApp al +54 9 11 6848-0793 (respondemos en el día)\n• Completar el formulario en pablofarias.com.ar\n• Solicitar una videollamada en el horario que te convenga\n\nAtendemos clientes en todo el país de forma remota.',
'[{"text":"WhatsApp ahora","value":"whatsapp"},{"text":"¿Primera consulta gratis?","value":"consulta gratis"}]',
0, 9, 1, 'Contacto'),

('gracias',
'¡Con mucho gusto! Cualquier otra consulta sobre trámites sucesorios no dudes en escribirnos. Recordá que la primera consulta con el Dr. Pablo Farias es **sin cargo**. WhatsApp: **+54 9 11 6848-0793**.',
'[{"text":"Otra consulta","value":"hola"},{"text":"WhatsApp","value":"whatsapp"}]',
0, 9, 1, 'Contacto'),

('adios',
'¡Hasta luego! Cuando lo necesites, el estudio **Pablo Farias Abogados** está a tu disposición. WhatsApp: +54 9 11 6848-0793 — primera consulta sin cargo.',
'[{"text":"Contactar luego","value":"contacto"}]',
0, 8, 1, 'Contacto');


-- ───────────────────────────────────────────────────────────────────
-- RESPUESTAS ENLATADAS (para agentes del estudio)
-- ───────────────────────────────────────────────────────────────────

INSERT INTO chat_canned (shortcut, title, content, category, is_active) VALUES

('bienvenida', 'Saludo inicial', 'Hola, soy asistente del estudio Pablo Farias Abogados. ¿En qué te puedo ayudar con tu trámite sucesorio?', 'Saludo', 1),

('consulta_gratis', 'Oferta consulta sin cargo', 'La primera consulta con el Dr. Pablo Farias es sin cargo, ya sea presencial, por videollamada o por WhatsApp. ¿Te puedo ayudar a coordinarla?', 'Saludo', 1),

('whatsapp_farias', 'Dar número de WhatsApp', 'Podés comunicarte directamente por WhatsApp al +54 9 11 6848-0793. El Dr. Farias o su equipo te responden a la brevedad.', 'Contacto', 1),

('pedido_docs', 'Solicitar documentación inicial', 'Para comenzar necesitamos: acta de defunción del causante, DNI del causante, actas de estado civil (matrimonio/nacimiento), y datos de los bienes (escrituras, títulos). ¿Con cuáles contás?', 'Documentación', 1),

('presupuesto', 'Oferta de presupuesto', 'El estudio trabaja con presupuesto por etapas y ofrece hasta 12 cuotas sin interés. Contándome el valor aproximado de los bienes y la jurisdicción puedo darte una estimación orientativa.', 'Honorarios', 1),

('sin_impuesto', 'Argentina no tiene impuesto a herencias', 'En Argentina no existe impuesto nacional a la herencia. Solo se abonan: tasa de justicia (3% CABA o 1,5% PBA del activo), gastos de escrituración y, en algunos casos, impuesto de sellos provincial.', 'Honorarios', 1),

('conflicto_herederos', 'Conflicto entre herederos', 'Entiendo la situación. En estos casos el estudio trabaja primero con mediación para alcanzar un acuerdo, que es siempre más rápido y económico que la vía litigiosa. Si no es posible, el juez puede ordenar la partición judicial y eventual subasta. ¿Me contás más sobre el conflicto?', 'Proceso', 1),

('internacional_consulta', 'Consulta sucesión internacional', 'El estudio está especializado en sucesiones con bienes o herederos en el exterior (España, Italia, Grecia, Uruguay, Brasil). Contame los países involucrados y el tipo de bienes para orientarte mejor.', 'Internacional', 1),

('plazo_proceso', 'Estimación de plazos', 'Los plazos orientativos son: sucesión simple 6-12 meses, con varios bienes/herederos 12-24 meses, internacional o conflictiva 2-4 años. Todo depende de la complejidad y la jurisdicción. ¿Me podés dar más detalles del caso?', 'Proceso', 1),

('renuncia', 'Explicar renuncia a herencia', 'La renuncia debe ser expresa (no se presume), realizada ante escribano público o en el expediente judicial. Es irrevocable. Antes de renunciar conviene verificar si las deudas superan los bienes, ya que los herederos no responden con su patrimonio personal.', 'Herencia', 1),

('urgencia_cautelar', 'Urgencia: medidas cautelares', 'Si hay riesgo de deterioro o pérdida de bienes, podemos solicitar medidas cautelares al juez desde el inicio del proceso. Esto protege el patrimonio mientras tramita la sucesión. ¿Hay una situación urgente?', 'Proceso', 1),

('cierre_consulta', 'Cierre de atención', 'Fue un placer orientarte. Ante cualquier duda adicional, recordá que el estudio Pablo Farias Abogados está a tu disposición en pablofarias.com.ar o por WhatsApp al +54 9 11 6848-0793. ¡Hasta pronto!', 'Contacto', 1);
