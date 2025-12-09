-- Initialize PostgreSQL with pgvector extension
CREATE EXTENSION IF NOT EXISTS vector;

-- Grant permissions to user
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO cbapp_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO cbapp_user;
GRANT ALL PRIVILEGES ON ALL FUNCTIONS IN SCHEMA public TO cbapp_user;