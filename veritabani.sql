-- Bilge Asistan | Neşe Sağır
-- phpMyAdmin > İçe Aktar

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS llm_proje
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE llm_proje;

CREATE TABLE IF NOT EXISTS bilgi_bankasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    icerik TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

TRUNCATE TABLE bilgi_bankasi;

INSERT INTO bilgi_bankasi (icerik) VALUES
('LLM (Large Language Model) nedir? LLM, büyük metin veri setleri üzerinde eğitilmiş dil modelleridir. Metin anlama, üretme ve soru yanıtlama yeteneklerine sahiptir. Örnekler: GPT-3.5, GPT-4.'),

('Yapay zeka nedir? Yapay zeka, bilgisayar sistemlerinin öğrenme, akıl yürütme ve problem çözme yeteneklerini simüle etmesidir. Makine öğrenmesi ve derin öğrenme bu alanın temel alt dallarıdır.'),

('RAG (Retrieval-Augmented Generation) nedir? RAG, modele cevap üretmeden önce dış kaynaklardan bilgi çekip bağlam olarak sunan bir mimaridir. Bilge Asistan bu yaklaşımı kullanır.'),

('GPT-3.5-turbo nedir? OpenAI tarafından geliştirilen, hızlı ve verimli bir dil modelidir. Bilge Asistan cevap üretimi için bu modeli kullanır.'),

('Sohbet botu nedir? Kullanıcılarla yazılı iletişim kurabilen otomatik yazılım sistemidir. Bilge Asistan bir sohbet botu uygulamasıdır.'),

('Bilge Asistan nedir? OpenAI API ve RAG mimarisi kullanan akıllı sohbet botudur. MySQL bilgi bankasından bağlam çekerek kullanıcıya yanıt verir.'),

('Senin kurucun kim? Bilge Asistan''ın geliştiricisi Neşe Sağır''dır.'),

('OpenAI nedir? Büyük dil modelleri geliştiren ve API hizmeti sunan bir teknoloji şirketidir. Bilge Asistan OpenAI API üzerinden çalışır.'),

('Bilgi bankası ne işe yarar? Sohbet botunun yanıt üretirken başvurduğu özel bilgi kaynağıdır. Veriler bilgi_bankasi tablosunda saklanır.');
