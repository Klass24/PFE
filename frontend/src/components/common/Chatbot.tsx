import { useState, useRef, useEffect } from 'react';
import {
  Box,
  Fab,
  Paper,
  Typography,
  IconButton,
  TextField,
  Stack,
  Avatar,
  Chip,
  Fade,
  Slide,
  CircularProgress,
} from '@mui/material';
import ChatIcon from '@mui/icons-material/Chat';
import CloseIcon from '@mui/icons-material/Close';
import SendRoundedIcon from '@mui/icons-material/SendRounded';
import SmartToyOutlinedIcon from '@mui/icons-material/SmartToyOutlined';
import PersonOutlineIcon from '@mui/icons-material/PersonOutline';
import RestartAltOutlinedIcon from '@mui/icons-material/RestartAltOutlined';
import { useNavigate } from 'react-router-dom';

interface Message {
  id: string;
  sender: 'bot' | 'user';
  text: string;
  timestamp: string;
  options?: string[];
  actionLink?: { text: string; path: string };
}

const FAQ_KNOWLEDGE_BASE: { keywords: string[]; response: string; action?: { text: string; path: string }; options?: string[] }[] = [
  {
    keywords: ['tarif', 'prix', 'coût', 'abonnement', 'payer', 'combien', 'pricing'],
    response: "FleetControl propose des forfaits flexibles selon la taille de votre flotte (Starter, Pro et Entreprise), avec un essai gratuit de 14 jours sans carte bancaire !",
    action: { text: "Voir nos tarifs", path: "/pricing" },
    options: ["Essai gratuit 14 jours", "Fonctionnalités incluses", "Parler à un conseiller"]
  },
  {
    keywords: ['fonctionnalité', 'features', 'module', 'que fait', 'service', 'possibilité'],
    response: "FleetControl intègre la gestion des véhicules, le suivi GPS en direct, la planification des missions, la gestion des chauffeurs, le suivi du carburant et des alertes de maintenance préventive.",
    options: ["Suivi GPS", "Gestion carburant", "Planifier une démo"]
  },
  {
    keywords: ['gps', 'géolocalisation', 'tracking', 'temps réel', 'carte', 'position'],
    response: "Notre module GPS vous permet de suivre la position précise de chaque véhicule en temps réel, d'analyser les itinéraires passés et de recevoir des alertes de géofencing.",
    options: ["Gestion missions", "Découvrir les tarifs", "Contacter l'équipe"]
  },
  {
    keywords: ['démo', 'demo', 'contact', 'conseiller', 'rendez-vous', 'support', 'aide', 'commercial'],
    response: "Notre équipe est disponible pour une démonstration personnalisée ou répondre à vos questions techniques et commerciales.",
    action: { text: "Prendre contact", path: "/contact" },
    options: ["Voir les tarifs", "Créer un compte"]
  },
  {
    keywords: ['essai', 'gratuit', 'inscrire', 'inscription', 'compte', 'tester', 'commencer'],
    response: "Vous pouvez démarrer dès aujourd'hui avec 14 jours d'essai gratuit, sans engagement ni carte bancaire.",
    action: { text: "Créer un compte gratuit", path: "/register" },
    options: ["Nos tarifs", "Contacter le support"]
  },
  {
    keywords: ['carburant', 'essence', 'diesel', 'consommation', 'plein'],
    response: "Le module Carburant surveille les consommations, détecte les anomalies ou surconsommations et centralise tous vos reçus de carburant.",
    options: ["Maintenance préventive", "Voir les tarifs"]
  },
  {
    keywords: ['maintenance', 'vidange', 'réparation', 'panne', 'garage'],
    response: "Planifiez les révisions et entretiens de vos véhicules à l'avance grâce à des rappels automatiques basés sur le kilométrage et les échéances calendaires.",
    options: ["Gestion des alertes", "Prendre contact"]
  }
];

const INITIAL_MESSAGES: Message[] = [
  {
    id: '1',
    sender: 'bot',
    text: "👋 Bonjour ! Je suis l'assistant virtuel FleetControl. Comment puis-je vous aider aujourd'hui ?",
    timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    options: [
      "Quelles sont les fonctionnalités ?",
      "Quels sont les tarifs ?",
      "Demander une démo",
      "Essai gratuit 14 jours"
    ]
  }
];

export default function Chatbot() {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState<Message[]>(INITIAL_MESSAGES);
  const [inputMessage, setInputMessage] = useState('');
  const [isTyping, setIsTyping] = useState(false);
  const [hasUnread, setHasUnread] = useState(true);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const navigate = useNavigate();

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    if (isOpen) {
      scrollToBottom();
      setHasUnread(false);
    }
  }, [messages, isOpen]);

  const handleSend = (textToSend?: string) => {
    const text = (textToSend || inputMessage).trim();
    if (!text) return;

    const userMsg: Message = {
      id: Date.now().toString(),
      sender: 'user',
      text,
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    };

    setMessages((prev) => [...prev, userMsg]);
    if (!textToSend) setInputMessage('');
    setIsTyping(true);

    // Simulate AI response delay
    setTimeout(() => {
      const lower = text.toLowerCase();
      let matched = FAQ_KNOWLEDGE_BASE.find(item =>
        item.keywords.some(k => lower.includes(k))
      );

      let responseText = "Merci pour votre message ! Un conseiller FleetControl peut également vous accompagner personnellement.";
      let action = undefined;
      let options = ["Voir nos tarifs", "Fonctionnalités", "Essai gratuit", "Contacter l'équipe"];

      if (matched) {
        responseText = matched.response;
        action = matched.action;
        if (matched.options) options = matched.options;
      } else if (lower.includes('bonjour') || lower.includes('salut') || lower.includes('hello')) {
        responseText = "Bonjour ! Ravi de vous accueillir sur FleetControl. Que souhaitez-vous découvrir sur notre solution de gestion de flotte ?";
      } else if (lower.includes('merci')) {
        responseText = "Avec grand plaisir ! N'hésitez pas si vous avez d'autres questions.";
        options = ["Voir les tarifs", "Créer un compte"];
      }

      const botMsg: Message = {
        id: (Date.now() + 1).toString(),
        sender: 'bot',
        text: responseText,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        options,
        actionLink: action
      };

      setMessages((prev) => [...prev, botMsg]);
      setIsTyping(false);
    }, 750);
  };

  const handleReset = () => {
    setMessages(INITIAL_MESSAGES);
  };

  return (
    <Box sx={{ position: 'fixed', bottom: 24, right: 24, zIndex: 1300 }}>
      {/* ───────────────── CHAT WINDOW ───────────────── */}
      <Slide direction="up" in={isOpen} mountOnEnter unmountOnExit>
        <Paper
          elevation={8}
          sx={{
            width: { xs: 'calc(100vw - 32px)', sm: 380 },
            height: 520,
            maxHeight: '85vh',
            display: 'flex',
            flexDirection: 'column',
            borderRadius: 3,
            overflow: 'hidden',
            boxShadow: '0 12px 36px rgba(0,0,0,0.18)',
            border: '1px solid #E2E8F0',
            mb: 2,
            bgcolor: '#FFFFFF'
          }}
        >
          {/* Header */}
          <Box
            sx={{
              p: 2,
              background: 'linear-gradient(135deg, #0F172A 0%, #1E3A5A 100%)',
              color: '#FFFFFF',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between'
            }}
          >
            <Stack direction="row" spacing={1.5} alignItems="center">
              <Avatar sx={{ bgcolor: '#2563EB', width: 40, height: 40 }}>
                <SmartToyOutlinedIcon />
              </Avatar>
              <Box>
                <Typography variant="subtitle1" sx={{ fontWeight: 600, fontSize: '0.95rem', lineHeight: 1.2 }}>
                  Assistant FleetControl
                </Typography>
                <Stack direction="row" spacing={0.8} alignItems="center">
                  <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: '#22C55E' }} />
                  <Typography variant="caption" sx={{ color: '#94A3B8', fontSize: '0.75rem' }}>
                    En ligne • Réponse instantanée
                  </Typography>
                </Stack>
              </Box>
            </Stack>

            <Stack direction="row" spacing={0.5}>
              <IconButton size="small" onClick={handleReset} sx={{ color: '#94A3B8', '&:hover': { color: '#FFFFFF' } }} title="Réinitialiser la conversation">
                <RestartAltOutlinedIcon fontSize="small" />
              </IconButton>
              <IconButton size="small" onClick={() => setIsOpen(false)} sx={{ color: '#94A3B8', '&:hover': { color: '#FFFFFF' } }}>
                <CloseIcon fontSize="small" />
              </IconButton>
            </Stack>
          </Box>

          {/* Messages Body */}
          <Box
            sx={{
              flex: 1,
              p: 2,
              overflowY: 'auto',
              bgcolor: '#F8FAFC',
              display: 'flex',
              flexDirection: 'column',
              gap: 1.5
            }}
          >
            {messages.map((msg) => (
              <Box
                key={msg.id}
                sx={{
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: msg.sender === 'user' ? 'flex-end' : 'flex-start',
                  maxWidth: '100%'
                }}
              >
                <Stack
                  direction="row"
                  spacing={1}
                  alignItems="flex-end"
                  sx={{
                    flexDirection: msg.sender === 'user' ? 'row-reverse' : 'row',
                    maxWidth: '85%'
                  }}
                >
                  <Avatar
                    sx={{
                      width: 26,
                      height: 26,
                      bgcolor: msg.sender === 'user' ? '#1E3A5A' : '#2563EB',
                      fontSize: 14
                    }}
                  >
                    {msg.sender === 'user' ? <PersonOutlineIcon sx={{ fontSize: 16 }} /> : <SmartToyOutlinedIcon sx={{ fontSize: 16 }} />}
                  </Avatar>

                  <Paper
                    elevation={0}
                    sx={{
                      p: 1.5,
                      borderRadius: 2.5,
                      bgcolor: msg.sender === 'user' ? '#2563EB' : '#FFFFFF',
                      color: msg.sender === 'user' ? '#FFFFFF' : '#0F172A',
                      border: msg.sender === 'user' ? 'none' : '1px solid #E2E8F0',
                      boxShadow: '0 1px 3px rgba(0,0,0,0.04)',
                      wordBreak: 'break-word'
                    }}
                  >
                    <Typography variant="body2" sx={{ fontSize: '0.88rem', lineHeight: 1.5 }}>
                      {msg.text}
                    </Typography>

                    {msg.actionLink && (
                      <Box sx={{ mt: 1.2 }}>
                        <Chip
                          label={msg.actionLink.text}
                          color="primary"
                          size="small"
                          clickable
                          onClick={() => {
                            navigate(msg.actionLink!.path);
                            setIsOpen(false);
                          }}
                          sx={{ fontWeight: 600, fontSize: '0.75rem', cursor: 'pointer' }}
                        />
                      </Box>
                    )}
                  </Paper>
                </Stack>

                <Typography variant="caption" sx={{ color: '#94A3B8', fontSize: '0.7rem', mt: 0.4, px: 4 }}>
                  {msg.timestamp}
                </Typography>

                {/* Suggestions chips */}
                {msg.options && msg.options.length > 0 && (
                  <Stack direction="row" spacing={0.8} flexWrap="wrap" sx={{ mt: 1, gap: 0.6, pl: 4 }}>
                    {msg.options.map((opt, idx) => (
                      <Chip
                        key={idx}
                        label={opt}
                        size="small"
                        clickable
                        onClick={() => handleSend(opt)}
                        sx={{
                          bgcolor: '#FFFFFF',
                          borderColor: '#CBD5E1',
                          color: '#1E3A5A',
                          fontSize: '0.75rem',
                          fontWeight: 500,
                          '&:hover': { bgcolor: '#EFF6FF', borderColor: '#2563EB', color: '#2563EB' }
                        }}
                        variant="outlined"
                      />
                    ))}
                  </Stack>
                )}
              </Box>
            ))}

            {isTyping && (
              <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, pl: 1 }}>
                <Avatar sx={{ width: 24, height: 24, bgcolor: '#2563EB' }}>
                  <SmartToyOutlinedIcon sx={{ fontSize: 14 }} />
                </Avatar>
                <Paper
                  elevation={0}
                  sx={{
                    px: 2,
                    py: 1,
                    borderRadius: 2,
                    bgcolor: '#FFFFFF',
                    border: '1px solid #E2E8F0',
                    display: 'flex',
                    alignItems: 'center',
                    gap: 0.8
                  }}
                >
                  <CircularProgress size={12} color="primary" />
                  <Typography variant="caption" sx={{ color: '#64748B' }}>
                    FleetControl réfléchit...
                  </Typography>
                </Paper>
              </Box>
            )}

            <div ref={messagesEndRef} />
          </Box>

          {/* Footer Input */}
          <Box
            component="form"
            onSubmit={(e) => {
              e.preventDefault();
              handleSend();
            }}
            sx={{
              p: 1.5,
              borderTop: '1px solid #E2E8F0',
              bgcolor: '#FFFFFF',
              display: 'flex',
              alignItems: 'center',
              gap: 1
            }}
          >
            <TextField
              fullWidth
              size="small"
              placeholder="Posez une question..."
              value={inputMessage}
              onChange={(e) => setInputMessage(e.target.value)}
              variant="outlined"
              sx={{
                '& .MuiOutlinedInput-root': {
                  borderRadius: 3,
                  fontSize: '0.88rem',
                  bgcolor: '#F8FAFC',
                }
              }}
            />
            <IconButton
              color="primary"
              type="submit"
              disabled={!inputMessage.trim()}
              sx={{
                bgcolor: '#2563EB',
                color: '#FFFFFF',
                width: 38,
                height: 38,
                '&:hover': { bgcolor: '#1D4ED8' },
                '&.Mui-disabled': { bgcolor: '#E2E8F0', color: '#94A3B8' }
              }}
            >
              <SendRoundedIcon sx={{ fontSize: 18 }} />
            </IconButton>
          </Box>
        </Paper>
      </Slide>

      {/* ───────────────── FLOATING ACTION BUTTON ───────────────── */}
      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-end', position: 'relative' }}>
        {/* Notification Bubble on first visit */}
        <Fade in={!isOpen && hasUnread}>
          <Paper
            elevation={4}
            sx={{
              mr: 2,
              px: 2,
              py: 1,
              bgcolor: '#0F172A',
              color: '#FFFFFF',
              borderRadius: 3,
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              gap: 1,
              boxShadow: '0 4px 14px rgba(0,0,0,0.15)',
              '&:hover': { bgcolor: '#1E293B' }
            }}
            onClick={() => setIsOpen(true)}
          >
            <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: '#22C55E' }} />
            <Typography variant="body2" sx={{ fontSize: '0.82rem', fontWeight: 500 }}>
              Besoin d'aide ? Discutez avec nous !
            </Typography>
          </Paper>
        </Fade>

        <Fab
          color="primary"
          aria-label="chat"
          onClick={() => setIsOpen((prev) => !prev)}
          sx={{
            bgcolor: isOpen ? '#0F172A' : '#2563EB',
            '&:hover': { bgcolor: isOpen ? '#1E293B' : '#1D4ED8' },
            width: 56,
            height: 56,
            boxShadow: '0 6px 20px rgba(37,99,235,0.4)',
            transition: 'all 0.3s ease'
          }}
        >
          {isOpen ? <CloseIcon /> : <ChatIcon />}
        </Fab>
      </Box>
    </Box>
  );
}
