-- ============================================================
--  MailGenius Pro — Contenido para Servicios / Consultoría
--  Ejecutar DESPUÉS de schema.sql
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- 1. RESPUESTAS DEL BOT DE CHAT
-- ============================================================
TRUNCATE TABLE chat_bot_responses;

INSERT INTO chat_bot_responses (trigger_word, response_text, quick_replies, is_exact_match, priority, is_active) VALUES

-- Saludos
('hola',
 '¡Hola! 👋 Bienvenido a nuestro servicio de consultoría. Estoy aquí para ayudarte. ¿En qué puedo orientarte hoy?',
 '[{"text":"Ver servicios","value":"servicios"},{"text":"Solicitar cotización","value":"cotización"},{"text":"Hablar con asesor","value":"agente"}]',
 0, 10, 1),

('buenos dias',
 '¡Buenos días! Que tengas un excelente día. ¿Cómo puedo ayudarte hoy?',
 '[{"text":"Ver servicios","value":"servicios"},{"text":"Solicitar cotización","value":"cotización"}]',
 0, 9, 1),

('buenas tardes',
 '¡Buenas tardes! ¿En qué te puedo ayudar?',
 '[{"text":"Ver servicios","value":"servicios"},{"text":"Contactar asesor","value":"agente"}]',
 0, 9, 1),

-- Precios y cotizaciones
('precio',
 'Nuestros precios varían según el alcance y complejidad de cada proyecto. Ofrecemos planes desde proyectos puntuales hasta esquemas de trabajo continuo (retainer). Para darte una cifra exacta, necesitamos conocer tus necesidades. ¿Te hago llegar una propuesta personalizada?',
 '[{"text":"Quiero una propuesta","value":"cotización"},{"text":"Ver servicios incluidos","value":"servicios"},{"text":"Hablar con asesor","value":"agente"}]',
 0, 10, 1),

('costo',
 'El costo depende del tipo de servicio y el tiempo requerido. Trabajamos con tarifas por proyecto o por hora, según lo que más te convenga. ¿Qué tipo de proyecto tienes en mente?',
 '[{"text":"Proyecto fijo","value":"proyecto fijo"},{"text":"Por horas","value":"horas"},{"text":"Retainer mensual","value":"retainer"}]',
 0, 9, 1),

('cotización',
 'Con gusto te preparamos una cotización. Solo necesito algunos datos:\n• ¿Qué tipo de servicio necesitas?\n• ¿Cuál es tu plazo estimado?\n• ¿Tienes un presupuesto aproximado?\nUn asesor te contactará en menos de 2 horas hábiles.',
 '[{"text":"Llenar formulario","value":"formulario"},{"text":"Llamarme ahora","value":"llamada"},{"text":"Enviar por email","value":"email"}]',
 0, 10, 1),

('presupuesto',
 'Entiendo que el presupuesto es clave. Tenemos opciones para diferentes escalas de inversión. ¿Puedes contarme un poco más sobre lo que necesitas para darte la mejor alternativa dentro de tu rango?',
 '[{"text":"Menos de $1,000","value":"plan basico"},{"text":"$1,000 - $5,000","value":"plan profesional"},{"text":"Más de $5,000","value":"plan enterprise"}]',
 0, 9, 1),

-- Servicios
('servicios',
 '🚀 Nuestros servicios principales son:\n\n• **Consultoría estratégica** — análisis, diagnóstico y hoja de ruta\n• **Transformación digital** — procesos, automatización y tecnología\n• **Desarrollo de software** — web, móvil y sistemas a medida\n• **Marketing digital** — SEO, redes y campañas\n• **Capacitación** — talleres y formación para equipos\n\n¿Cuál te interesa más?',
 '[{"text":"Consultoría","value":"consultoría"},{"text":"Desarrollo","value":"desarrollo"},{"text":"Marketing","value":"marketing"},{"text":"Capacitación","value":"capacitación"}]',
 0, 9, 1),

('consultoría',
 'Nuestra consultoría abarca diagnóstico organizacional, optimización de procesos, transformación digital y planificación estratégica. Trabajamos con empresas de todos los tamaños. ¿Cuál es el principal desafío que quieres resolver?',
 '[{"text":"Diagnóstico empresarial","value":"diagnóstico"},{"text":"Optimizar procesos","value":"procesos"},{"text":"Hablar con consultor","value":"agente"}]',
 0, 8, 1),

('desarrollo',
 'Desarrollamos soluciones tecnológicas a medida: aplicaciones web, apps móviles, sistemas de gestión, APIs e integraciones. Utilizamos tecnologías modernas y metodologías ágiles. ¿Qué tipo de proyecto tienes?',
 '[{"text":"Aplicación web","value":"web"},{"text":"App móvil","value":"móvil"},{"text":"Sistema interno","value":"sistema"},{"text":"Ver portfolio","value":"portfolio"}]',
 0, 8, 1),

('marketing',
 'Nuestro equipo de marketing digital te ayuda con: SEO y posicionamiento, gestión de redes sociales, campañas de email marketing, publicidad pagada (Google/Meta) y analítica web. ¿Por dónde quieres empezar?',
 '[{"text":"SEO y contenidos","value":"seo"},{"text":"Redes sociales","value":"redes"},{"text":"Publicidad pagada","value":"pauta"},{"text":"Ver casos de éxito","value":"portfolio"}]',
 0, 8, 1),

-- Tiempos y plazos
('tiempo',
 'Los plazos varían según la complejidad:\n• Consultoría inicial: 1-2 semanas\n• Proyectos web medianos: 4-8 semanas\n• Sistemas complejos: 3-6 meses\n• Campañas de marketing: inicio inmediato\n\nSiempre definimos un calendario detallado al inicio del proyecto.',
 '[{"text":"Necesito algo urgente","value":"urgente"},{"text":"Tengo tiempo","value":"cotización"}]',
 0, 9, 1),

('plazo',
 'Nos adaptamos a tus plazos. Cuéntame qué necesitas y para cuándo, y te decimos con honestidad si es factible y cómo lograrlo.',
 '[{"text":"Es urgente","value":"urgente"},{"text":"En 1 mes","value":"cotización"},{"text":"Sin apuro","value":"cotización"}]',
 0, 8, 1),

('urgente',
 '⚡ Entendemos la urgencia. Contamos con un equipo disponible para proyectos prioritarios. Voy a conectarte ahora mismo con un asesor para evaluar tu caso.',
 '[{"text":"Contactar ahora","value":"agente"}]',
 0, 10, 1),

-- Contacto
('contacto',
 '📞 Puedes contactarnos por:\n• **Email:** info@tuempresa.com\n• **Teléfono:** +XX XXX XXX XXXX\n• **WhatsApp:** +XX XXX XXX XXXX\n• **Horario:** Lunes a Viernes, 9:00 - 18:00\n\n¿Prefieres que te contactemos nosotros?',
 '[{"text":"Que me llamen","value":"llamada"},{"text":"Enviar email","value":"email"},{"text":"Chatear ahora","value":"agente"}]',
 0, 9, 1),

('teléfono',
 'Nuestro número de contacto es +XX XXX XXX XXXX. Atendemos de Lunes a Viernes de 9:00 a 18:00 horas. ¿Quieres que un asesor te llame a ti?',
 '[{"text":"Sí, que me llamen","value":"llamada"},{"text":"Prefiero chat","value":"agente"}]',
 0, 8, 1),

('horario',
 '🕐 Nuestro horario de atención es:\n• Lunes a Viernes: 9:00 - 18:00\n• Sábados: 9:00 - 13:00\n• Domingos y festivos: cerrado\n\nFuera de horario puedes dejarnos un mensaje y te respondemos al siguiente día hábil.',
 '[{"text":"Dejar mensaje","value":"email"},{"text":"Hablar ahora","value":"agente"}]',
 0, 8, 1),

-- Soporte
('soporte',
 '🛠️ Para soporte técnico necesito saber:\n• ¿Eres cliente actual?\n• ¿Cuál es el problema o error?\n• ¿Cuándo comenzó?\n\nSi es un cliente con contrato de mantenimiento activo, tenemos SLA garantizado.',
 '[{"text":"Soy cliente actual","value":"cliente soporte"},{"text":"Quiero contratar soporte","value":"cotización"},{"text":"Hablar con técnico","value":"agente"}]',
 0, 9, 1),

('problema',
 'Lamentamos que tengas un inconveniente. Estoy aquí para ayudarte a resolverlo. ¿Puedes describir brevemente qué está ocurriendo?',
 '[{"text":"Es urgente","value":"urgente"},{"text":"No es urgente","value":"soporte"}]',
 0, 8, 1),

-- Reuniones y demos
('reunión',
 '📅 Con gusto agendamos una reunión. Tenemos disponibilidad esta semana. ¿Cuándo te viene mejor?\n\nLas reuniones de diagnóstico inicial son sin costo y sin compromiso.',
 '[{"text":"Esta semana","value":"agendar"},{"text":"La próxima semana","value":"agendar"},{"text":"Elegir fecha","value":"agendar"}]',
 0, 9, 1),

('demo',
 '🎯 ¡Excelente idea! Una demostración es la mejor forma de ver nuestro trabajo en acción. Podemos hacer una demo personalizada de 30-45 minutos adaptada a tu industria. ¿Cuándo tienes disponibilidad?',
 '[{"text":"Hoy","value":"agendar"},{"text":"Esta semana","value":"agendar"},{"text":"La próxima","value":"agendar"}]',
 0, 9, 1),

-- Pagos y facturas
('pago',
 '💳 Aceptamos las siguientes formas de pago:\n• Transferencia bancaria\n• Tarjeta de crédito/débito\n• PayPal\n• Factura a 30 días (clientes con historial)\n\nGeneralmente trabajamos con 50% al inicio y 50% a la entrega.',
 '[{"text":"Más información","value":"agente"},{"text":"Solicitar cotización","value":"cotización"}]',
 0, 8, 1),

('factura',
 'Emitimos factura fiscal por todos nuestros servicios. Podemos facturar a persona física o moral, en moneda local o USD según tu preferencia.',
 '[{"text":"Ver datos fiscales","value":"agente"},{"text":"Solicitar factura","value":"agente"}]',
 0, 8, 1),

-- Portfolio y referencias
('portfolio',
 '🏆 Hemos trabajado con más de 50 empresas en sectores como retail, salud, finanzas y manufactura. Nuestros casos incluyen reducción de costos operativos del 35%, incremento de ventas digitales del 120% y automatización de procesos críticos.\n\n¿Quieres ver casos específicos de tu industria?',
 '[{"text":"Casos de retail","value":"casos retail"},{"text":"Casos de salud","value":"casos salud"},{"text":"Hablar con referencias","value":"agente"}]',
 0, 8, 1),

('experiencia',
 'Llevamos más de 8 años en el mercado. Nuestro equipo está formado por consultores certificados con experiencia en empresas Fortune 500 y startups de alto crecimiento. Combinamos visión estratégica con ejecución práctica.',
 '[{"text":"Ver equipo","value":"equipo"},{"text":"Ver casos de éxito","value":"portfolio"},{"text":"Hablar con nosotros","value":"agente"}]',
 0, 7, 1),

-- Garantías y contratos
('garantía',
 'Respaldamos nuestro trabajo con:\n• Revisiones sin costo durante el período de garantía\n• Acuerdos de nivel de servicio (SLA) documentados\n• Contratos claros con entregables definidos\n• Soporte post-entrega incluido\n\n¿Quieres conocer los detalles de nuestras garantías?',
 '[{"text":"Ver contrato tipo","value":"agente"},{"text":"Hablar con asesor","value":"agente"}]',
 0, 7, 1),

('contrato',
 'Trabajamos siempre con contrato. Nuestros acuerdos definen claramente alcance, entregables, plazos y condiciones de pago. Usamos contratos estándar que pueden ser revisados por tu equipo legal.',
 '[{"text":"Ver modelo de contrato","value":"agente"},{"text":"Hacer preguntas","value":"agente"}]',
 0, 7, 1),

-- Despedidas y agradecimientos
('gracias',
 '¡Con mucho gusto! Es un placer poder ayudarte. Si tienes más preguntas, aquí estaré. ¿Hay algo más en lo que pueda asistirte?',
 '[{"text":"No, gracias","value":"adios"},{"text":"Tengo otra pregunta","value":"servicios"}]',
 0, 6, 1),

('adios',
 '¡Hasta luego! Ha sido un placer atenderte. No dudes en volver si necesitas algo. ¡Que tengas un excelente día! 😊',
 '[]',
 0, 6, 1),

-- Escalamiento a humano
('agente',
 'Entendido, te conecto con uno de nuestros asesores ahora mismo. Por favor, en breve alguien del equipo tomará esta conversación. ¿Puedes dejarnos tu nombre y una breve descripción de lo que necesitas?',
 '[]',
 0, 10, 1),

('humano',
 'Por supuesto, te transfiero con un asesor humano de inmediato. Mientras esperas, ¿puedes contarnos brevemente qué necesitas para asignarte al especialista correcto?',
 '[{"text":"Ventas","value":"ventas"},{"text":"Soporte técnico","value":"soporte"},{"text":"Facturación","value":"factura"}]',
 0, 10, 1);


-- ============================================================
-- 2. RESPUESTAS ENLATADAS (para agentes)
-- ============================================================
TRUNCATE TABLE chat_canned;

INSERT INTO chat_canned (title, shortcut, content) VALUES
('Saludo inicial', 'saludo',
 '¡Hola! Bienvenido/a. Mi nombre es [NOMBRE], soy asesor/a de [EMPRESA]. ¿En qué puedo ayudarte hoy?'),

('Solicitar más información', 'info',
 'Para poder orientarte mejor, ¿podrías contarme un poco más sobre lo que necesitas? Específicamente me ayudaría saber: el tipo de proyecto, el plazo estimado y el alcance aproximado.'),

('Confirmar recepción de solicitud', 'recibido',
 'Hemos recibido tu solicitud correctamente. Un especialista revisará los detalles y te enviaremos una propuesta personalizada en un plazo máximo de 24 horas hábiles.'),

('Agendar reunión de seguimiento', 'reunion',
 'Me gustaría agendar una llamada/reunión para discutir los detalles. ¿Tienes disponibilidad esta semana? Puedes elegir el horario que mejor te convenga y te enviaré la invitación.'),

('Escalar a especialista técnico', 'tecnico',
 'Este tema requiere la atención de uno de nuestros especialistas técnicos. Voy a transferir la conversación ahora. Por favor espera un momento, te atenderán en breve.'),

('Tiempo de respuesta estándar', 'tiempo',
 'Nuestro tiempo de respuesta habitual es de 2-4 horas en horario laboral (L-V 9:00-18:00). Para consultas urgentes, contamos con atención prioritaria.'),

('Cierre de conversación', 'cierre',
 '¡Fue un placer ayudarte! Si en el futuro necesitas más información o tienes alguna duda, no dudes en contactarnos. Que tengas excelente día.'),

('Solicitar datos de contacto', 'datos',
 'Para poder darte seguimiento personalizado, ¿podrías compartirme tu nombre completo, empresa y email de contacto? Así podré asignarte al asesor especializado en tu sector.'),

('Disponibilidad para llamada', 'llamada',
 '¿Te parece bien si coordinamos una llamada? Tengo disponibilidad hoy a las [HORA] o mañana por la mañana. ¿Cuál de esas opciones te viene mejor?'),

('Propuesta en camino', 'propuesta',
 'Estoy preparando la propuesta con toda la información que me has dado. Te la enviaré a tu email en las próximas [X horas]. ¿Hay algo adicional que debería incluir?'),

('Confirmar inicio de proyecto', 'inicio',
 '¡Excelente noticia! Confirmamos el inicio del proyecto para [FECHA]. Tu project manager asignado es [NOMBRE] y se pondrá en contacto contigo en las próximas 24 horas para coordinar el kickoff.'),

('Solicitar retroalimentación', 'feedback',
 '¿Cómo calificarías la atención recibida hoy? Tu opinión es muy importante para nosotros y nos ayuda a mejorar continuamente el servicio.');


-- ============================================================
-- 3. CATEGORÍAS DE RESPUESTA
-- ============================================================
INSERT IGNORE INTO response_categories (name, description, color) VALUES
('Ventas', 'Consultas sobre precios, cotizaciones y servicios', '#6366f1'),
('Soporte', 'Problemas técnicos y asistencia post-venta', '#ef4444'),
('Administrativo', 'Facturación, contratos y pagos', '#f59e0b'),
('Marketing', 'Información de campañas y materiales', '#22c55e'),
('Urgente', 'Casos que requieren atención inmediata', '#dc2626');


-- ============================================================
-- 4. CATEGORÍAS DE PLANTILLAS
-- ============================================================
INSERT IGNORE INTO template_categories (name) VALUES
('Ventas y Cotizaciones'),
('Bienvenida y Onboarding'),
('Seguimiento'),
('Soporte Técnico'),
('Administrativo');


-- ============================================================
-- 5. PLANTILLAS DE EMAIL
-- ============================================================
DELETE FROM templates;

INSERT INTO templates (name, subject, body, category_id, variables, usage_count) VALUES

('Propuesta Comercial', 'Propuesta de servicios para {{empresa}}',
'<div style="font-family:Arial,sans-serif;max-width:680px;margin:0 auto;color:#1e293b">
  <div style="background:#6366f1;padding:32px;border-radius:12px 12px 0 0;text-align:center">
    <h1 style="color:#fff;margin:0;font-size:24px">Propuesta de Servicios</h1>
    <p style="color:rgba(255,255,255,.8);margin:8px 0 0">Preparada especialmente para {{empresa}}</p>
  </div>
  <div style="background:#fff;padding:32px;border-radius:0 0 12px 12px;border:1px solid #e2e8f0">
    <p>Estimado/a <strong>{{nombre}}</strong>,</p>
    <p>Fue un placer conversar contigo sobre las necesidades de <strong>{{empresa}}</strong>. A continuación te presento nuestra propuesta para <strong>{{servicio}}</strong>.</p>
    <h2 style="color:#6366f1;font-size:18px;border-bottom:2px solid #e2e8f0;padding-bottom:8px">Alcance del Proyecto</h2>
    <p>{{descripcion_alcance}}</p>
    <h2 style="color:#6366f1;font-size:18px;border-bottom:2px solid #e2e8f0;padding-bottom:8px">Inversión</h2>
    <table style="width:100%;border-collapse:collapse">
      <tr style="background:#f8fafc">
        <td style="padding:12px;border:1px solid #e2e8f0"><strong>Fase 1 — {{fase1}}</strong></td>
        <td style="padding:12px;border:1px solid #e2e8f0;text-align:right"><strong>{{precio_fase1}}</strong></td>
      </tr>
      <tr>
        <td style="padding:12px;border:1px solid #e2e8f0">Fase 2 — {{fase2}}</td>
        <td style="padding:12px;border:1px solid #e2e8f0;text-align:right">{{precio_fase2}}</td>
      </tr>
      <tr style="background:#6366f1;color:#fff">
        <td style="padding:12px;border:1px solid #4f46e5"><strong>INVERSIÓN TOTAL</strong></td>
        <td style="padding:12px;border:1px solid #4f46e5;text-align:right"><strong>{{precio_total}}</strong></td>
      </tr>
    </table>
    <h2 style="color:#6366f1;font-size:18px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-top:24px">Cronograma</h2>
    <p>⏱️ Plazo estimado: <strong>{{plazo}}</strong><br>📅 Inicio propuesto: <strong>{{fecha_inicio}}</strong></p>
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:16px;margin:24px 0">
      <p style="margin:0;color:#166534">✅ Esta propuesta tiene validez de <strong>15 días hábiles</strong>. Incluye soporte post-entrega de 30 días.</p>
    </div>
    <p>Para aceptar la propuesta o resolver cualquier duda, responde a este email o agenda una llamada.</p>
    <p style="margin-top:32px">Saludos cordiales,<br><strong>{{asesor_nombre}}</strong><br>{{asesor_cargo}}<br>{{empresa_emisora}}</p>
  </div>
</div>',
(SELECT id FROM template_categories WHERE name = 'Ventas y Cotizaciones' LIMIT 1),
'["empresa","nombre","servicio","descripcion_alcance","fase1","precio_fase1","fase2","precio_fase2","precio_total","plazo","fecha_inicio","asesor_nombre","asesor_cargo","empresa_emisora"]',
0),


('Confirmación de Reunión', 'Confirmado: Reunión con {{empresa}} — {{fecha}}',
'<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#0f172a;padding:24px;border-radius:12px 12px 0 0;text-align:center">
    <p style="color:#6366f1;font-size:28px;margin:0">📅</p>
    <h1 style="color:#fff;margin:8px 0 0;font-size:20px">Reunión Confirmada</h1>
  </div>
  <div style="background:#fff;padding:32px;border-radius:0 0 12px 12px;border:1px solid #e2e8f0">
    <p>Hola <strong>{{nombre}}</strong>,</p>
    <p>Tu reunión ha sido confirmada. Aquí los detalles:</p>
    <div style="background:#f8fafc;border-left:4px solid #6366f1;padding:20px;border-radius:0 8px 8px 0;margin:20px 0">
      <p style="margin:4px 0">📅 <strong>Fecha:</strong> {{fecha}}</p>
      <p style="margin:4px 0">🕐 <strong>Hora:</strong> {{hora}} ({{zona_horaria}})</p>
      <p style="margin:4px 0">⏱️ <strong>Duración:</strong> {{duracion}}</p>
      <p style="margin:4px 0">📍 <strong>Modalidad:</strong> {{modalidad}}</p>
      <p style="margin:4px 0">🔗 <strong>Enlace:</strong> <a href="{{enlace_reunion}}" style="color:#6366f1">{{enlace_reunion}}</a></p>
    </div>
    <p><strong>Agenda de la reunión:</strong></p>
    <ol style="padding-left:20px;line-height:2">
      <li>Presentación y contexto (10 min)</li>
      <li>{{punto_agenda_1}}</li>
      <li>{{punto_agenda_2}}</li>
      <li>Preguntas y próximos pasos (10 min)</li>
    </ol>
    <p style="color:#64748b;font-size:14px">Si necesitas reagendar, responde a este email con al menos 2 horas de anticipación.</p>
    <p>¡Nos vemos pronto!<br><strong>{{asesor_nombre}}</strong></p>
  </div>
</div>',
(SELECT id FROM template_categories WHERE name = 'Ventas y Cotizaciones' LIMIT 1),
'["nombre","empresa","fecha","hora","zona_horaria","duracion","modalidad","enlace_reunion","punto_agenda_1","punto_agenda_2","asesor_nombre"]',
0),


('Bienvenida Nuevo Cliente', 'Bienvenido/a a bordo, {{nombre}} 🎉',
'<div style="font-family:Arial,sans-serif;max-width:640px;margin:0 auto;color:#1e293b">
  <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:48px 32px;border-radius:12px 12px 0 0;text-align:center">
    <p style="font-size:48px;margin:0">🎉</p>
    <h1 style="color:#fff;margin:12px 0 4px;font-size:26px">¡Bienvenido/a!</h1>
    <p style="color:rgba(255,255,255,.85);margin:0">Estamos muy contentos de trabajar con {{empresa}}</p>
  </div>
  <div style="background:#fff;padding:32px;border-radius:0 0 12px 12px;border:1px solid #e2e8f0">
    <p>Hola <strong>{{nombre}}</strong>,</p>
    <p>Es un honor que hayas elegido trabajar con nosotros. Estamos comprometidos a darte los mejores resultados.</p>
    <h2 style="color:#6366f1;font-size:17px">📋 Próximos pasos</h2>
    <div style="display:flex;flex-direction:column;gap:12px">
      <div style="display:flex;align-items:flex-start;gap:12px;padding:12px;background:#f8fafc;border-radius:8px">
        <span style="background:#6366f1;color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0">1</span>
        <div><strong>Kickoff Meeting</strong><br><span style="color:#64748b;font-size:14px">{{fecha_kickoff}} — Presentaremos al equipo y definiremos el plan de trabajo</span></div>
      </div>
      <div style="display:flex;align-items:flex-start;gap:12px;padding:12px;background:#f8fafc;border-radius:8px">
        <span style="background:#6366f1;color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0">2</span>
        <div><strong>Acceso a herramientas</strong><br><span style="color:#64748b;font-size:14px">Recibirás las credenciales de acceso a nuestro portal de cliente</span></div>
      </div>
      <div style="display:flex;align-items:flex-start;gap:12px;padding:12px;background:#f8fafc;border-radius:8px">
        <span style="background:#6366f1;color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0">3</span>
        <div><strong>Primer entregable</strong><br><span style="color:#64748b;font-size:14px">{{primer_entregable}} — Fecha estimada: {{fecha_primer_entregable}}</span></div>
      </div>
    </div>
    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:16px;margin:24px 0">
      <p style="margin:0;color:#1e40af">📞 Tu punto de contacto principal es <strong>{{pm_nombre}}</strong> (<a href="mailto:{{pm_email}}" style="color:#6366f1">{{pm_email}}</a>). No dudes en escribirle con cualquier consulta.</p>
    </div>
    <p>¡Comenzamos! 🚀</p>
    <p>El equipo de <strong>{{empresa_emisora}}</strong></p>
  </div>
</div>',
(SELECT id FROM template_categories WHERE name = 'Bienvenida y Onboarding' LIMIT 1),
'["nombre","empresa","fecha_kickoff","primer_entregable","fecha_primer_entregable","pm_nombre","pm_email","empresa_emisora"]',
0),


('Seguimiento de Cotización', 'Seguimiento: Propuesta para {{empresa}} — ¿Alguna pregunta?',
'<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#1e293b;padding:28px;border-radius:12px 12px 0 0;text-align:center">
    <h1 style="color:#fff;margin:0;font-size:20px">Seguimiento de Propuesta</h1>
  </div>
  <div style="background:#fff;padding:32px;border-radius:0 0 12px 12px;border:1px solid #e2e8f0">
    <p>Hola <strong>{{nombre}}</strong>,</p>
    <p>Hace {{dias}} días te envié la propuesta para <strong>{{servicio}}</strong> y quería asegurarme de que la hayas recibido y no tengas ninguna duda.</p>
    <p>Si tienes preguntas sobre:</p>
    <ul style="padding-left:20px;line-height:2">
      <li>El alcance o metodología de trabajo</li>
      <li>Los plazos o fases del proyecto</li>
      <li>La inversión o condiciones de pago</li>
      <li>Referencias de proyectos similares</li>
    </ul>
    <p>...estaré encantado/a de resolverlas en una llamada de 15-20 minutos.</p>
    <div style="text-align:center;margin:28px 0">
      <a href="{{enlace_agenda}}" style="background:#6366f1;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block">Agendar llamada ahora</a>
    </div>
    <p style="color:#64748b;font-size:13px">Recuerda que la propuesta tiene validez hasta el <strong>{{fecha_vencimiento}}</strong>.</p>
    <p>Saludos,<br><strong>{{asesor_nombre}}</strong></p>
  </div>
</div>',
(SELECT id FROM template_categories WHERE name = 'Seguimiento' LIMIT 1),
'["nombre","empresa","dias","servicio","enlace_agenda","fecha_vencimiento","asesor_nombre"]',
0),


('Entrega de Proyecto', 'Entrega completada: {{proyecto}} ✅',
'<div style="font-family:Arial,sans-serif;max-width:640px;margin:0 auto;color:#1e293b">
  <div style="background:#16a34a;padding:32px;border-radius:12px 12px 0 0;text-align:center">
    <p style="font-size:40px;margin:0">✅</p>
    <h1 style="color:#fff;margin:8px 0 0;font-size:22px">¡Proyecto Entregado!</h1>
    <p style="color:rgba(255,255,255,.85);margin:4px 0 0">{{proyecto}}</p>
  </div>
  <div style="background:#fff;padding:32px;border-radius:0 0 12px 12px;border:1px solid #e2e8f0">
    <p>Hola <strong>{{nombre}}</strong>,</p>
    <p>Con satisfacción te informamos que hemos completado la entrega de <strong>{{proyecto}}</strong>. A continuación el resumen:</p>
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:20px;margin:20px 0">
      <p style="margin:4px 0">📦 <strong>Entregables:</strong> {{lista_entregables}}</p>
      <p style="margin:4px 0">📅 <strong>Fecha de entrega:</strong> {{fecha_entrega}}</p>
      <p style="margin:4px 0">🔗 <strong>Acceso:</strong> {{enlace_entregables}}</p>
    </div>
    <h2 style="color:#16a34a;font-size:17px">🛡️ Garantía y soporte post-entrega</h2>
    <p>A partir de hoy comienzas con <strong>{{dias_garantia}} días de garantía</strong> que incluyen correcciones de errores sin costo adicional. Tu período de garantía vence el <strong>{{fecha_fin_garantia}}</strong>.</p>
    <p>Para reportar cualquier ajuste, envía un email a <a href="mailto:soporte@tuempresa.com" style="color:#6366f1">soporte@tuempresa.com</a> indicando el número de proyecto <strong>#{{numero_proyecto}}</strong>.</p>
    <p>¡Fue un placer trabajar contigo! Esperamos contar con tu confianza en futuros proyectos.</p>
    <p>El equipo de <strong>{{empresa_emisora}}</strong></p>
  </div>
</div>',
(SELECT id FROM template_categories WHERE name = 'Bienvenida y Onboarding' LIMIT 1),
'["nombre","proyecto","lista_entregables","fecha_entrega","enlace_entregables","dias_garantia","fecha_fin_garantia","numero_proyecto","empresa_emisora"]',
0),


('Solicitud de Testimonio', 'Tu opinión nos ayuda a crecer — ¿Nos compartes tu experiencia?',
'<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#1e293b">
  <div style="background:#f59e0b;padding:28px;border-radius:12px 12px 0 0;text-align:center">
    <p style="font-size:36px;margin:0">⭐</p>
    <h1 style="color:#fff;margin:8px 0 0;font-size:20px">¿Cómo fue tu experiencia?</h1>
  </div>
  <div style="background:#fff;padding:32px;border-radius:0 0 12px 12px;border:1px solid #e2e8f0">
    <p>Hola <strong>{{nombre}}</strong>,</p>
    <p>Han pasado {{semanas}} semanas desde que completamos <strong>{{proyecto}}</strong> y nos encantaría saber cómo te ha ido con los resultados.</p>
    <p>¿Podrías tomarte 2 minutos para compartir tu experiencia? Tu testimonio ayuda a otras empresas a tomar decisiones informadas.</p>
    <div style="text-align:center;margin:28px 0">
      <a href="{{enlace_testimonio}}" style="background:#f59e0b;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block">✍️ Dejar testimonio</a>
    </div>
    <p style="color:#64748b;font-size:13px">Solo toma 2 minutos. Si lo prefieres, también puedes responder directamente a este email.</p>
    <p>¡Muchas gracias!<br><strong>{{asesor_nombre}}</strong></p>
  </div>
</div>',
(SELECT id FROM template_categories WHERE name = 'Seguimiento' LIMIT 1),
'["nombre","proyecto","semanas","enlace_testimonio","asesor_nombre"]',
0),


('Respuesta a Consulta de Soporte', 'Re: Ticket #{{ticket_id}} — {{asunto}}',
'<div style="font-family:Arial,sans-serif;max-width:640px;margin:0 auto;color:#1e293b">
  <div style="background:#1e293b;padding:24px;border-radius:12px 12px 0 0">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h1 style="color:#fff;margin:0;font-size:18px">Respuesta de Soporte</h1>
      <span style="background:#6366f1;color:#fff;padding:4px 12px;border-radius:20px;font-size:13px">Ticket #{{ticket_id}}</span>
    </div>
  </div>
  <div style="background:#fff;padding:32px;border-radius:0 0 12px 12px;border:1px solid #e2e8f0">
    <p>Hola <strong>{{nombre}}</strong>,</p>
    <p>Gracias por contactarnos. Hemos revisado tu caso y aquí está nuestra respuesta:</p>
    <div style="background:#f8fafc;border-left:4px solid #6366f1;padding:16px;border-radius:0 8px 8px 0;margin:20px 0">
      <p style="margin:0"><strong>Problema reportado:</strong> {{descripcion_problema}}</p>
    </div>
    <h2 style="font-size:16px;color:#6366f1">💡 Solución</h2>
    <p>{{solucion}}</p>
    {{pasos_adicionales}}
    <p>Si el problema persiste o tienes alguna duda adicional, responde a este email con el número de ticket <strong>#{{ticket_id}}</strong> en el asunto.</p>
    <p style="color:#64748b;font-size:13px">⏱️ Tiempo de resolución del ticket: {{tiempo_resolucion}}</p>
    <p>Saludos,<br><strong>{{agente_nombre}}</strong><br>Equipo de Soporte Técnico</p>
  </div>
</div>',
(SELECT id FROM template_categories WHERE name = 'Soporte Técnico' LIMIT 1),
'["nombre","ticket_id","asunto","descripcion_problema","solucion","pasos_adicionales","tiempo_resolucion","agente_nombre"]',
0);


-- ============================================================
-- 6. REGLAS DE RESPUESTA AUTOMÁTICA
-- ============================================================
DELETE FROM response_rules;

INSERT INTO response_rules (name, conditions, action, action_value, category_id, priority, is_active) VALUES

('Cotización urgente — respuesta prioritaria',
 '[{"field":"subject","operator":"contains","value":"urgente"},{"field":"subject","operator":"contains","value":"cotización"}]',
 'auto_reply',
 'Hemos recibido tu solicitud marcada como urgente. Un asesor senior te contactará en los próximos 30 minutos durante horario hábil (L-V 9:00-18:00). Si es fuera de horario, te atenderemos a primera hora del próximo día hábil. Número de caso: #AUTO-URGENTE-' || FLOOR(RAND()*9000+1000),
 (SELECT id FROM response_categories WHERE name = 'Urgente' LIMIT 1),
 100, 1),

('Solicitud de cotización — respuesta automática',
 '[{"field":"subject","operator":"contains","value":"cotización"}]',
 'auto_reply',
 'Hola, gracias por solicitar información sobre nuestros servicios. Hemos recibido tu mensaje y un asesor especializado te enviará una propuesta personalizada en un plazo máximo de 4 horas hábiles. Mientras tanto, puedes conocer más sobre nuestros servicios en nuestro sitio web.',
 (SELECT id FROM response_categories WHERE name = 'Ventas' LIMIT 1),
 90, 1),

('Presupuesto — derivar a ventas',
 '[{"field":"subject","operator":"contains","value":"presupuesto"}]',
 'forward',
 'ventas@tuempresa.com',
 (SELECT id FROM response_categories WHERE name = 'Ventas' LIMIT 1),
 85, 1),

('Factura o pago — derivar a administración',
 '[{"field":"subject","operator":"contains","value":"factura"}]',
 'forward',
 'admin@tuempresa.com',
 (SELECT id FROM response_categories WHERE name = 'Administrativo' LIMIT 1),
 80, 1),

('Soporte técnico — crear ticket',
 '[{"field":"subject","operator":"contains","value":"soporte"},{"field":"subject","operator":"contains","value":"error"}]',
 'auto_reply',
 'Hemos recibido tu reporte de soporte técnico. Se ha creado un ticket de atención y nuestro equipo técnico lo revisará en breve. Para seguimiento, guarda este número de referencia y responde a este mismo correo indicándolo.',
 (SELECT id FROM response_categories WHERE name = 'Soporte' LIMIT 1),
 75, 1),

('Email de cancelación — alerta a ventas',
 '[{"field":"body","operator":"contains","value":"cancelar"},{"field":"body","operator":"contains","value":"cancelación"}]',
 'forward',
 'ventas@tuempresa.com',
 (SELECT id FROM response_categories WHERE name = 'Ventas' LIMIT 1),
 70, 1),

('Newsletter y dominio desconocido — ignorar',
 '[{"field":"from","operator":"contains","value":"noreply"},{"field":"from","operator":"contains","value":"newsletter"}]',
 'ignore',
 'ignored',
 NULL,
 10, 1);


-- ============================================================
-- 7. DIAGRAMAS DE FLUJO
-- ============================================================
DELETE FROM diagrams;

-- DIAGRAMA 1: Solicitud de Cotización
INSERT INTO diagrams (name, description, nodes, connections, is_active) VALUES
('Solicitud de Cotización', 'Flujo para calificar y procesar solicitudes de cotización entrantes',
'[
  {"id":"node1","type":"start","label":"Inicio","x":320,"y":20,"data":{}},
  {"id":"node2","type":"response","label":"Bienvenida","x":270,"y":110,"data":{"response":"¡Bienvenido! Gracias por tu interés en nuestros servicios. Para prepararte la mejor propuesta, necesito hacerte algunas preguntas breves. ¿Qué tipo de servicio estás buscando?"}},
  {"id":"node3","type":"decision","label":"Tipo de Servicio","x":270,"y":230,"data":{"condition":"desarrollo, consultoría, marketing, diseño, capacitación"}},
  {"id":"node4","type":"response","label":"Desarrollo de Software","x":40,"y":370,"data":{"response":"Excelente. Trabajamos con aplicaciones web, móviles y sistemas a medida. ¿Tienes ya definidos los requerimientos o necesitas apoyo con el análisis y diseño?"}},
  {"id":"node5","type":"response","label":"Consultoría Estratégica","x":270,"y":370,"data":{"response":"Nos especializamos en diagnóstico organizacional, transformación digital y optimización de procesos. ¿Cuál es el principal desafío que quieres resolver en tu empresa?"}},
  {"id":"node6","type":"response","label":"Marketing Digital","x":500,"y":370,"data":{"response":"Gestionamos SEO, redes sociales, publicidad digital y email marketing. ¿Tienes ya presencia online o estás construyéndola desde cero?"}},
  {"id":"node7","type":"decision","label":"¿Tiene Presupuesto?","x":155,"y":510,"data":{"condition":"sí, tengo, presupuesto, disponible"}},
  {"id":"node8","type":"action","label":"Notificar Equipo Ventas","x":40,"y":640,"data":{"action_type":"notify","action_value":"ventas"}},
  {"id":"node9","type":"action","label":"Enviar Propuesta Estándar","x":270,"y":640,"data":{"action_type":"template","action_value":"1"}},
  {"id":"node10","type":"action","label":"Enviar Brochure","x":500,"y":640,"data":{"action_type":"template","action_value":"2"}},
  {"id":"node11","type":"end","label":"Cotización Iniciada","x":270,"y":760,"data":{}}
]',
'[
  {"id":"c1","source":"node1","target":"node2","label":""},
  {"id":"c2","source":"node2","target":"node3","label":""},
  {"id":"c3","source":"node3","target":"node4","label":"Desarrollo"},
  {"id":"c4","source":"node3","target":"node5","label":"Consultoría"},
  {"id":"c5","source":"node3","target":"node6","label":"Marketing"},
  {"id":"c6","source":"node4","target":"node7","label":""},
  {"id":"c7","source":"node7","target":"node8","label":"Sí"},
  {"id":"c8","source":"node7","target":"node9","label":"No definido"},
  {"id":"c9","source":"node5","target":"node9","label":""},
  {"id":"c10","source":"node6","target":"node10","label":""},
  {"id":"c11","source":"node8","target":"node11","label":""},
  {"id":"c12","source":"node9","target":"node11","label":""},
  {"id":"c13","source":"node10","target":"node11","label":""}
]',
1),


-- DIAGRAMA 2: Soporte Técnico
('Soporte Técnico', 'Flujo de clasificación y escalamiento de tickets de soporte',
'[
  {"id":"node1","type":"start","label":"Inicio","x":300,"y":20,"data":{}},
  {"id":"node2","type":"response","label":"Identificar Cliente","x":250,"y":110,"data":{"response":"Hola, bienvenido al soporte técnico. Para ayudarte mejor, ¿eres cliente actual con contrato de soporte activo?"}},
  {"id":"node3","type":"decision","label":"¿Es Cliente Activo?","x":250,"y":230,"data":{"condition":"sí, cliente, tengo contrato"}},
  {"id":"node4","type":"decision","label":"¿Prioridad del Problema?","x":100,"y":370,"data":{"condition":"urgente, crítico, producción, caído, bloqueado"}},
  {"id":"node5","type":"response","label":"No es Cliente","x":420,"y":370,"data":{"response":"Para acceder a soporte técnico es necesario contar con un contrato de mantenimiento activo. ¿Te gustaría conocer nuestros planes de soporte?"}},
  {"id":"node6","type":"action","label":"Escalar: Guardia Urgente","x":40,"y":510,"data":{"action_type":"notify","action_value":"guardia_urgente"}},
  {"id":"node7","type":"action","label":"Crear Ticket Normal","x":200,"y":510,"data":{"action_type":"tag","action_value":"soporte_normal"}},
  {"id":"node8","type":"action","label":"Enviar Info Planes Soporte","x":420,"y":510,"data":{"action_type":"template","action_value":"3"}},
  {"id":"node9","type":"response","label":"Confirmación Ticket","x":120,"y":640,"data":{"response":"Hemos registrado tu ticket de soporte. Un técnico te contactará en el tiempo de respuesta acordado en tu SLA. Tu número de ticket es #[AUTO]."}},
  {"id":"node10","type":"end","label":"Ticket Procesado","x":250,"y":750,"data":{}}
]',
'[
  {"id":"c1","source":"node1","target":"node2","label":""},
  {"id":"c2","source":"node2","target":"node3","label":""},
  {"id":"c3","source":"node3","target":"node4","label":"Sí"},
  {"id":"c4","source":"node3","target":"node5","label":"No"},
  {"id":"c5","source":"node4","target":"node6","label":"Urgente"},
  {"id":"c6","source":"node4","target":"node7","label":"Normal"},
  {"id":"c7","source":"node5","target":"node8","label":""},
  {"id":"c8","source":"node6","target":"node9","label":""},
  {"id":"c9","source":"node7","target":"node9","label":""},
  {"id":"c10","source":"node8","target":"node10","label":""},
  {"id":"c11","source":"node9","target":"node10","label":""}
]',
1),


-- DIAGRAMA 3: Calificación de Lead
('Calificación de Lead', 'Flujo para calificar prospectos según presupuesto, urgencia y tamaño de empresa',
'[
  {"id":"node1","type":"start","label":"Lead Entrante","x":300,"y":20,"data":{}},
  {"id":"node2","type":"decision","label":"¿Tiene Presupuesto Definido?","x":240,"y":110,"data":{"condition":"presupuesto, inversión, dinero, budget"}},
  {"id":"node3","type":"decision","label":"Tamaño de Empresa","x":100,"y":250,"data":{"condition":"empresa, empleados, tamaño, grande, pyme"}},
  {"id":"node4","type":"response","label":"Lead Frío — Nutrir","x":420,"y":250,"data":{"response":"Entendemos que aún no tienes definido el presupuesto. Te enviamos información de valor sobre nuestros servicios para que puedas evaluarlos con calma. ¿Cuándo estimas tomar una decisión?"}},
  {"id":"node5","type":"action","label":"Asignar a Ventas Senior","x":40,"y":390,"data":{"action_type":"notify","action_value":"ventas_senior"}},
  {"id":"node6","type":"action","label":"Enviar Propuesta PYME","x":200,"y":390,"data":{"action_type":"template","action_value":"1"}},
  {"id":"node7","type":"action","label":"Enviar Brochure + Seguimiento","x":420,"y":390,"data":{"action_type":"template","action_value":"4"}},
  {"id":"node8","type":"response","label":"Respuesta Corporativa","x":40,"y":520,"data":{"response":"Excelente, vemos que tienes una operación de escala corporativa. Te asignamos un ejecutivo de cuenta senior que se especializará en tus necesidades. ¿Cuándo podemos agendar una reunión de descubrimiento?"}},
  {"id":"node9","type":"response","label":"Respuesta PYME","x":200,"y":520,"data":{"response":"Para empresas como la tuya tenemos paquetes especialmente diseñados con una excelente relación precio-valor. Te enviamos nuestra propuesta para PYMES ahora mismo."}},
  {"id":"node10","type":"end","label":"Lead Calificado","x":240,"y":640,"data":{}}
]',
'[
  {"id":"c1","source":"node1","target":"node2","label":""},
  {"id":"c2","source":"node2","target":"node3","label":"Sí"},
  {"id":"c3","source":"node2","target":"node4","label":"No"},
  {"id":"c4","source":"node3","target":"node5","label":"Corporativo (>50 empleados)"},
  {"id":"c5","source":"node3","target":"node6","label":"PYME (10-50)"},
  {"id":"c6","source":"node4","target":"node7","label":""},
  {"id":"c7","source":"node5","target":"node8","label":""},
  {"id":"c8","source":"node6","target":"node9","label":""},
  {"id":"c9","source":"node7","target":"node10","label":""},
  {"id":"c10","source":"node8","target":"node10","label":""},
  {"id":"c11","source":"node9","target":"node10","label":""}
]',
1),


-- DIAGRAMA 4: FAQ General
('Consulta General — FAQ', 'Flujo de respuestas frecuentes para preguntas generales de visitantes',
'[
  {"id":"node1","type":"start","label":"Consulta Entrante","x":300,"y":20,"data":{}},
  {"id":"node2","type":"response","label":"Bienvenida FAQ","x":250,"y":110,"data":{"response":"¡Hola! Estoy aquí para responder tus preguntas. ¿Sobre qué tema necesitas información?"}},
  {"id":"node3","type":"decision","label":"Tema de la Consulta","x":250,"y":230,"data":{"condition":"precio, tiempo, contacto, experiencia, garantía, proceso"}},
  {"id":"node4","type":"response","label":"Resp: Precios","x":20,"y":390,"data":{"response":"Nuestros precios varían según el tipo y alcance del proyecto. Trabajamos con presupuestos desde proyectos pequeños hasta implementaciones enterprise. Lo mejor es que nos cuentes tu caso y te preparamos una propuesta sin costo."}},
  {"id":"node5","type":"response","label":"Resp: Tiempos","x":160,"y":390,"data":{"response":"Los plazos típicos son: Consultoría inicial (1-2 semanas), Proyectos web medianos (4-8 semanas), Sistemas complejos (3-6 meses). Siempre definimos un cronograma detallado al inicio."}},
  {"id":"node6","type":"response","label":"Resp: Proceso de Trabajo","x":300,"y":390,"data":{"response":"Nuestro proceso tiene 4 etapas: 1) Descubrimiento y análisis, 2) Diseño de solución, 3) Ejecución con revisiones periódicas, 4) Entrega y soporte post-proyecto. Usamos metodologías ágiles."}},
  {"id":"node7","type":"response","label":"Resp: Garantías","x":440,"y":390,"data":{"response":"Ofrecemos garantía de satisfacción en todos nuestros proyectos. Incluimos soporte post-entrega de 30 días, correcciones sin costo y SLA documentado. Todo queda especificado en el contrato."}},
  {"id":"node8","type":"action","label":"Conectar con Asesor","x":580,"y":390,"data":{"action_type":"notify","action_value":"ventas"}},
  {"id":"node9","type":"end","label":"Consulta Respondida","x":300,"y":560,"data":{}}
]',
'[
  {"id":"c1","source":"node1","target":"node2","label":""},
  {"id":"c2","source":"node2","target":"node3","label":""},
  {"id":"c3","source":"node3","target":"node4","label":"Precio / Costo"},
  {"id":"c4","source":"node3","target":"node5","label":"Tiempo / Plazo"},
  {"id":"c5","source":"node3","target":"node6","label":"Proceso / Metodología"},
  {"id":"c6","source":"node3","target":"node7","label":"Garantía / Contrato"},
  {"id":"c7","source":"node3","target":"node8","label":"Otro tema"},
  {"id":"c8","source":"node4","target":"node9","label":""},
  {"id":"c9","source":"node5","target":"node9","label":""},
  {"id":"c10","source":"node6","target":"node9","label":""},
  {"id":"c11","source":"node7","target":"node9","label":""},
  {"id":"c12","source":"node8","target":"node9","label":""}
]',
1);

-- ============================================================
SELECT 'Seed completado exitosamente.' AS resultado;
-- ============================================================
