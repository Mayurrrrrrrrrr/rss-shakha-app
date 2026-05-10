USE sanghasthan;
CREATE TABLE IF NOT EXISTS personalities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    title VARCHAR(255),
    description TEXT,
    image_path VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO personalities (name, title, description, display_order) VALUES
('Dr. K. B. Hedgewar', '1889–1940', 'Founder of the RSS in 1925. He laid the foundation of the organization in Nagpur with the aim of uniting Hindu society for national regeneration.', 1),
('M. S. Golwalkar', '1906–1973', 'Known as "Guruji," he was the second and longest-serving Sarsanghchalak (1940–1973). He expanded the organization nationally and provided its ideological framework, emphasizing Hindu nationalism.', 2),
('Madhukar Dattatraya Deoras', '1915–1996', 'Also known as Balasaheb Deoras, he was the third chief (1973–1994). He steered the Sangh through the Emergency and emphasized a more active social role for the organization.', 3),
('Rajendra Singh', '1922–2003', 'Known as "Rajju Bhaiya," the fourth Sarsanghchalak (1994–2000). He was the first non-Maharashtrian and non-Brahmin chief, overseeing a period of growth for the Bharatiya Janata Party (BJP).', 4),
('K. S. Sudarshan', '1931–2012', 'The fifth chief (2000–2009). A proponent of Swadeshi (indigenous economic policies), he often advocated for closer alignment with the organization\'s core ideology.', 5),
('Mohan Bhagwat', 'born 1950', 'The sixth and current Sarsanghchalak (2009–present). He has guided the organization during its period of highest political influence, supporting a modern and tech-savvy approach.', 6),
('Dattatreya Hosabale', 'born 1954', 'The current Sarkaryavah (General Secretary) since 2021, making him the functional executive head of the organization, responsible for day-to-day operations.', 7),
('Deendayal Upadhyaya', '1916–1968', 'A prominent thinker and proponent of Integral Humanism. While he worked primarily through the Bharatiya Jana Sangh (precursor to the BJP), he was a dedicated pracharak (full-time worker) nurtured by the RSS.', 8),
('H. V. Seshadri', '1926–2005', 'A top strategist and intellectual, he served as the Sarkaryavah and was known for his significant contributions to the ideological literature of the Sangh.', 9),
('Laxmibai Kelkar', '1884–1978', 'Founder of the Rashtra Sevika Samiti (the women\'s wing of the RSS) in 1936, which works on similar ideological lines for women.', 10);
