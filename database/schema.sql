-- ============================================================
-- MailGenius Pro - Sistema de Gestión de Comunicaciones
-- Schema MySQL 8.0+
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Users
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','user') DEFAULT 'user',
  `avatar` VARCHAR(255),
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` DATETIME,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Contact Groups
CREATE TABLE `contact_groups` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `color` VARCHAR(7) DEFAULT '#4f46e5',
  `contact_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Contacts
CREATE TABLE `contacts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `phone` VARCHAR(30),
  `company` VARCHAR(150),
  `position` VARCHAR(150),
  `group_id` INT UNSIGNED,
  `tags` JSON,
  `custom_fields` JSON,
  `is_subscribed` TINYINT(1) DEFAULT 1,
  `bounce_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`group_id`) REFERENCES `contact_groups`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Template Categories
CREATE TABLE `template_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `color` VARCHAR(7) DEFAULT '#06b6d4',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Templates
CREATE TABLE `templates` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `subject` VARCHAR(300) NOT NULL,
  `body_html` LONGTEXT NOT NULL,
  `body_text` LONGTEXT,
  `variables` JSON,
  `thumbnail` VARCHAR(255),
  `is_active` TINYINT(1) DEFAULT 1,
  `usage_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `template_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Emails (sent, draft, scheduled, received)
CREATE TABLE `emails` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED,
  `type` ENUM('sent','draft','scheduled','received') NOT NULL DEFAULT 'draft',
  `from_name` VARCHAR(150),
  `from_email` VARCHAR(150) NOT NULL,
  `to_emails` JSON NOT NULL,
  `cc_emails` JSON,
  `bcc_emails` JSON,
  `subject` VARCHAR(500) NOT NULL,
  `body_html` LONGTEXT,
  `body_text` LONGTEXT,
  `template_id` INT UNSIGNED,
  `campaign_id` INT UNSIGNED,
  `thread_id` VARCHAR(36),
  `reply_to_id` INT UNSIGNED,
  `message_id` VARCHAR(200),
  `priority` ENUM('low','normal','high','urgent') DEFAULT 'normal',
  `scheduled_at` DATETIME,
  `sent_at` DATETIME,
  `is_important` TINYINT(1) DEFAULT 0,
  `is_read` TINYINT(1) DEFAULT 0,
  `labels` JSON,
  `metadata` JSON,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE SET NULL,
  INDEX `idx_type_created` (`type`, `created_at`),
  INDEX `idx_scheduled` (`scheduled_at`)
) ENGINE=InnoDB;

-- Attachments
CREATE TABLE `email_attachments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email_id` INT UNSIGNED NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `size` BIGINT NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `is_inline` TINYINT(1) DEFAULT 0,
  `cid` VARCHAR(100),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`email_id`) REFERENCES `emails`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Email tracking
CREATE TABLE `email_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email_id` INT UNSIGNED NOT NULL,
  `contact_id` INT UNSIGNED,
  `recipient_email` VARCHAR(150),
  `event_type` ENUM('queued','sent','delivered','opened','clicked','bounced','unsubscribed','spam','failed') NOT NULL,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `link_url` VARCHAR(1000),
  `metadata` JSON,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`email_id`) REFERENCES `emails`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`) ON DELETE SET NULL,
  INDEX `idx_email_event` (`email_id`, `event_type`)
) ENGINE=InnoDB;

-- Campaigns
CREATE TABLE `campaigns` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `from_name` VARCHAR(150) NOT NULL,
  `from_email` VARCHAR(150) NOT NULL,
  `reply_to` VARCHAR(150),
  `subject` VARCHAR(500) NOT NULL,
  `body_html` LONGTEXT NOT NULL,
  `body_text` LONGTEXT,
  `template_id` INT UNSIGNED,
  `group_id` INT UNSIGNED,
  `status` ENUM('draft','scheduled','running','completed','paused','cancelled') DEFAULT 'draft',
  `scheduled_at` DATETIME,
  `started_at` DATETIME,
  `completed_at` DATETIME,
  `total_recipients` INT DEFAULT 0,
  `sent_count` INT DEFAULT 0,
  `open_count` INT DEFAULT 0,
  `click_count` INT DEFAULT 0,
  `bounce_count` INT DEFAULT 0,
  `unsubscribe_count` INT DEFAULT 0,
  `batch_size` INT DEFAULT 50,
  `delay_between_batches` INT DEFAULT 60,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`group_id`) REFERENCES `contact_groups`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Campaign contacts
CREATE TABLE `campaign_contacts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` INT UNSIGNED NOT NULL,
  `contact_id` INT UNSIGNED,
  `email` VARCHAR(150) NOT NULL,
  `status` ENUM('pending','sending','sent','failed','opened','clicked','bounced','unsubscribed') DEFAULT 'pending',
  `sent_at` DATETIME,
  `opened_at` DATETIME,
  `clicked_at` DATETIME,
  `error_message` TEXT,
  FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`) ON DELETE SET NULL,
  INDEX `idx_campaign_status` (`campaign_id`, `status`)
) ENGINE=InnoDB;

-- Response categories
CREATE TABLE `response_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `color` VARCHAR(7) DEFAULT '#06b6d4',
  `icon` VARCHAR(50) DEFAULT 'chat-dots',
  `priority` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Intelligent response rules
CREATE TABLE `response_rules` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `category_id` INT UNSIGNED,
  `conditions` JSON NOT NULL,
  `condition_logic` ENUM('AND','OR') DEFAULT 'AND',
  `action_type` ENUM('auto_reply','forward','apply_template','route_to_diagram','tag','ignore') NOT NULL,
  `action_data` JSON,
  `is_active` TINYINT(1) DEFAULT 1,
  `priority` INT DEFAULT 0,
  `trigger_count` INT DEFAULT 0,
  `last_triggered` DATETIME,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `response_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Response flow diagrams
CREATE TABLE `diagrams` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `category_id` INT UNSIGNED,
  `nodes` JSON NOT NULL,
  `connections` JSON NOT NULL,
  `canvas_settings` JSON,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `response_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Scheduled email queue
CREATE TABLE `scheduled_queue` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email_id` INT UNSIGNED NOT NULL,
  `scheduled_at` DATETIME NOT NULL,
  `status` ENUM('pending','processing','sent','failed','cancelled') DEFAULT 'pending',
  `attempts` INT DEFAULT 0,
  `max_attempts` INT DEFAULT 3,
  `last_attempt` DATETIME,
  `error_message` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`email_id`) REFERENCES `emails`(`id`) ON DELETE CASCADE,
  INDEX `idx_scheduled_status` (`scheduled_at`, `status`)
) ENGINE=InnoDB;

-- System settings
CREATE TABLE `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) UNIQUE NOT NULL,
  `setting_value` TEXT,
  `setting_type` ENUM('string','integer','boolean','json') DEFAULT 'string',
  `description` VARCHAR(255),
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Default data
-- ============================================================

INSERT INTO `template_categories` (`name`, `description`, `color`) VALUES
('Bienvenida',    'Plantillas para nuevos contactos',          '#10b981'),
('Soporte',       'Respuestas de soporte técnico',             '#4f46e5'),
('Marketing',     'Campañas de marketing y promociones',       '#f59e0b'),
('Ventas',        'Seguimiento y cierre de ventas',            '#ef4444'),
('Notificaciones','Avisos automáticos del sistema',            '#06b6d4'),
('Transaccional', 'Confirmaciones, facturas y recibos',        '#8b5cf6');

INSERT INTO `response_categories` (`name`, `description`, `color`, `icon`, `priority`) VALUES
('Consulta General',  'Preguntas generales de información',    '#06b6d4', 'question-circle',   1),
('Soporte Técnico',   'Problemas técnicos y reportes de bugs', '#ef4444', 'tools',             2),
('Facturación',       'Consultas sobre facturas y pagos',      '#f59e0b', 'credit-card',       3),
('Solicitud de Demo', 'Peticiones de demostración',            '#10b981', 'camera-video',      4),
('Queja/Reclamo',     'Quejas y reclamos de clientes',         '#dc2626', 'exclamation-circle',5),
('Cancelación',       'Solicitudes de cancelación',            '#6b7280', 'x-circle',          6);

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('smtp_host',            'smtp.hostinger.com',            'string',  'Servidor SMTP'),
('smtp_port',            '587',                           'integer', 'Puerto SMTP'),
('smtp_user',            'sucesiones@fariasortiz.com.ar', 'string',  'Usuario SMTP'),
('smtp_password',        '',                              'string',  'Contraseña SMTP'),
('smtp_encryption',      'tls',                           'string',  'Tipo de cifrado'),
('from_name',            'Pablo Farias Abogados',         'string',  'Nombre del remitente'),
('from_email',           'sucesiones@fariasortiz.com.ar', 'string',  'Email del remitente'),
('max_attachment_size',  '26214400',                      'integer', 'Tamaño máximo adjuntos (25MB)'),
('emails_per_hour',      '200',                           'integer', 'Límite emails por hora'),
('auto_reply_enabled',   '1',                             'boolean', 'Respuestas automáticas'),
('tracking_enabled',     '1',                             'boolean', 'Tracking de emails'),
('app_name',             'Farias Ortiz - Sucesiones',     'string',  'Nombre de la aplicación'),
('app_timezone',         'America/Buenos_Aires',          'string',  'Zona horaria'),
('app_url',              'https://www.sucesionlegal.com.ar/mailgenius', 'string', 'URL base de la app');

-- Admin user
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`) VALUES
('Pablo Farias', 'sucesiones@fariasortiz.com.ar', '$2y$12$1cIESt8Pd3KQS1wvyGKUTutmzo0XmliHGzIx5QAVGe4zdWzel.dNa', 'admin');

-- ============================================================
-- MÓDULO CHAT INTERNO
-- ============================================================

-- Chat sessions (conversations)
CREATE TABLE `chat_sessions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_token` VARCHAR(64) UNIQUE NOT NULL,
  `visitor_name` VARCHAR(100),
  `visitor_email` VARCHAR(150),
  `visitor_ip` VARCHAR(45),
  `visitor_country` VARCHAR(50),
  `page_url` VARCHAR(500),
  `referrer` VARCHAR(500),
  `agent_id` INT UNSIGNED,
  `department` VARCHAR(100),
  `status` ENUM('waiting','active','closed','missed','bot') DEFAULT 'waiting',
  `priority` ENUM('low','normal','high','urgent') DEFAULT 'normal',
  `category_id` INT UNSIGNED,
  `tags` JSON,
  `rating` TINYINT,
  `rating_comment` TEXT,
  `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `first_response_at` DATETIME,
  `closed_at` DATETIME,
  `converted_to_email` TINYINT(1) DEFAULT 0,
  `email_id` INT UNSIGNED,
  FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`category_id`) REFERENCES `response_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`email_id`) REFERENCES `emails`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Chat messages
CREATE TABLE `chat_messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT UNSIGNED NOT NULL,
  `sender_type` ENUM('visitor','agent','bot','system') NOT NULL,
  `sender_id` INT UNSIGNED,
  `sender_name` VARCHAR(100),
  `message_type` ENUM('text','image','file','audio','video','template','card','typing') DEFAULT 'text',
  `content` TEXT NOT NULL,
  `attachment_path` VARCHAR(500),
  `attachment_name` VARCHAR(255),
  `attachment_size` BIGINT,
  `attachment_mime` VARCHAR(100),
  `metadata` JSON,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` DATETIME,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `chat_sessions`(`id`) ON DELETE CASCADE,
  INDEX `idx_session_created` (`session_id`, `created_at`)
) ENGINE=InnoDB;

-- Chat bot responses (automated bot flows)
CREATE TABLE `chat_bot_responses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `trigger_keywords` JSON NOT NULL,
  `response_text` TEXT NOT NULL,
  `response_type` ENUM('text','quick_replies','card','transfer') DEFAULT 'text',
  `quick_replies` JSON,
  `next_action` ENUM('continue','transfer_to_agent','close','collect_email') DEFAULT 'continue',
  `is_active` TINYINT(1) DEFAULT 1,
  `priority` INT DEFAULT 0,
  `trigger_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Chat canned responses (quick replies for agents)
CREATE TABLE `chat_canned` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `shortcut` VARCHAR(50) UNIQUE NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `content` TEXT NOT NULL,
  `category` VARCHAR(100),
  `is_active` TINYINT(1) DEFAULT 1,
  `usage_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Chat widget configuration
CREATE TABLE `chat_widgets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `domain` VARCHAR(255),
  `primary_color` VARCHAR(7) DEFAULT '#4f46e5',
  `position` ENUM('bottom-right','bottom-left') DEFAULT 'bottom-right',
  `welcome_message` TEXT,
  `offline_message` TEXT,
  `collect_name` TINYINT(1) DEFAULT 1,
  `collect_email` TINYINT(1) DEFAULT 1,
  `show_agent_avatar` TINYINT(1) DEFAULT 1,
  `bot_enabled` TINYINT(1) DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `api_key` VARCHAR(64) UNIQUE NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Notifications (unified for email + chat)
CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('new_email','new_chat','chat_assigned','email_reply','campaign_done','rule_triggered','system') NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `body` TEXT,
  `link` VARCHAR(500),
  `source_type` ENUM('email','chat','campaign','system') DEFAULT 'system',
  `source_id` INT UNSIGNED,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB;

-- Default chat bot responses
INSERT INTO `chat_bot_responses` (`trigger_keywords`, `response_text`, `response_type`, `quick_replies`, `next_action`) VALUES
('["hola","hello","hi","buenos días","buenas"]',
 '¡Hola! Bienvenido al soporte. Soy el asistente virtual. ¿En qué puedo ayudarte?',
 'quick_replies',
 '[{"text":"💼 Consulta de ventas","value":"ventas"},{"text":"🔧 Soporte técnico","value":"soporte"},{"text":"💰 Facturación","value":"facturación"},{"text":"👤 Hablar con un agente","value":"agente"}]',
 'continue'),
('["precio","costo","cuánto","planes","tarifa"]',
 'Con gusto te informo sobre nuestros planes y precios. ¿Prefieres que te contacte un especialista de ventas?',
 'quick_replies',
 '[{"text":"Sí, quiero info de precios","value":"precios_si"},{"text":"No, solo tengo una duda rápida","value":"precios_no"}]',
 'continue'),
('["problema","error","falla","no funciona","ayuda"]',
 'Entiendo que tienes un inconveniente técnico. Para atenderte mejor, ¿puedes describir qué está pasando?',
 'text', NULL, 'transfer_to_agent'),
('["agente","humano","persona","operador"]',
 'Te voy a conectar con uno de nuestros agentes. Por favor, dame un momento.',
 'text', NULL, 'transfer_to_agent');

-- Default canned responses
INSERT INTO `chat_canned` (`shortcut`, `title`, `content`, `category`) VALUES
('/saludo',   'Saludo inicial',      '¡Hola {{nombre}}! Bienvenido al soporte de {{empresa}}. ¿En qué puedo ayudarte hoy?', 'General'),
('/espera',   'Pedir espera',        'Un momento por favor, estoy revisando tu consulta. Gracias por tu paciencia.', 'General'),
('/gracias',  'Agradecimiento',      '¡Gracias por contactarnos! Fue un placer ayudarte. Si necesitas algo más, no dudes en escribirnos.', 'General'),
('/cierre',   'Cierre de sesión',    '¿Hay algo más en lo que pueda ayudarte? Si no, procederé a cerrar esta conversación. ¡Que tengas un excelente día!', 'General'),
('/ticket',   'Crear ticket',        'He registrado tu consulta con el número de ticket #{{ticket}}. Te responderemos por email a {{email}} dentro de las próximas 24hs.', 'Soporte'),
('/escalar',  'Escalar caso',        'Entiendo tu situación. Voy a escalar tu caso a un especialista que podrá ayudarte mejor. Te contactarán a la brevedad.', 'Soporte');

-- Default widget
INSERT INTO `chat_widgets` (`name`, `domain`, `welcome_message`, `offline_message`, `api_key`) VALUES
('Widget Principal', '*', '¡Hola! 👋 ¿En qué podemos ayudarte hoy?', 'En este momento no hay agentes disponibles. Deja tu mensaje y te responderemos pronto.', REPLACE(UUID(), '-', ''));
