CREATE TABLE IF NOT EXISTS user_feeds (
    id BIGSERIAL PRIMARY KEY,
    from_user_id BIGINT NOT NULL,
    to_user_id BIGINT NOT NULL,
    text TEXT NOT NULL,
    created_at timestamp
);



