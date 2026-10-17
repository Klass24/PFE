import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../data/auth_repository.dart';

// Airframe palette constants
const _kNavy    = Color(0xFF1F2D3D);
const _kBlue    = Color(0xFF0275D8);
const _kBgGray  = Color(0xFFF0F3F4);
const _kBorder  = Color(0xFFDDE3EC);
const _kMuted   = Color(0xFF7F8FA4);
const _kDanger  = Color(0xFFD9534F);

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey   = GlobalKey<FormState>();
  final _emailCtrl = TextEditingController();
  final _passCtrl  = TextEditingController();
  bool _loading      = false;
  bool _obscure      = true;
  String? _error;

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() { _loading = true; _error = null; });
    try {
      final repo = context.read<AuthRepository>();
      await repo.login(_emailCtrl.text.trim(), _passCtrl.text);
      if (mounted) Navigator.pushReplacementNamed(context, '/home');
    } catch (_) {
      setState(() { _error = 'Email ou mot de passe incorrect.'; });
    } finally {
      if (mounted) setState(() { _loading = false; });
    }
  }

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kBgGray,
      body: Column(
        children: [
          // ── Header hero navy ──────────────────────────────────────────────
          Container(
            width: double.infinity,
            color: _kNavy,
            padding: const EdgeInsets.fromLTRB(24, 60, 24, 36),
            child: Column(
              children: [
                Container(
                  width: 68, height: 68,
                  decoration: BoxDecoration(
                    color: _kBlue,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: const Icon(Icons.local_shipping_rounded, size: 36, color: Colors.white),
                ),
                const SizedBox(height: 14),
                const Text(
                  'FleetControl',
                  style: TextStyle(
                    fontFamily: 'Inter', fontSize: 24, fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'Gestion de flotte intelligente',
                  style: TextStyle(fontFamily: 'Inter', fontSize: 13, color: Colors.white.withOpacity(0.6)),
                ),
              ],
            ),
          ),

          // ── Form card ─────────────────────────────────────────────────────
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: _kBorder),
                  boxShadow: const [BoxShadow(color: Color(0x0A000000), blurRadius: 6, offset: Offset(0, 2))],
                ),
                padding: const EdgeInsets.all(24),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Connexion',
                        style: TextStyle(fontFamily: 'Inter', fontSize: 18, fontWeight: FontWeight.w700, color: _kNavy),
                      ),
                      const SizedBox(height: 4),
                      const Text(
                        'Entrez vos identifiants pour accéder à votre compte',
                        style: TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kMuted),
                      ),
                      const SizedBox(height: 24),

                      // Error banner
                      if (_error != null) ...[
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFDF3F3),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(color: const Color(0xFFF5C6CB)),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.error_outline, color: _kDanger, size: 18),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(_error!,
                                    style: const TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kDanger)),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 18),
                      ],

                      // Email
                      const Text('Adresse email',
                          style: TextStyle(fontFamily: 'Inter', fontSize: 13, fontWeight: FontWeight.w600, color: _kNavy)),
                      const SizedBox(height: 6),
                      TextFormField(
                        controller: _emailCtrl,
                        keyboardType: TextInputType.emailAddress,
                        style: const TextStyle(fontFamily: 'Inter', fontSize: 14),
                        decoration: InputDecoration(
                          hintText: 'vous@exemple.com',
                          prefixIcon: const Icon(Icons.email_outlined, size: 20, color: _kMuted),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(6),
                            borderSide: const BorderSide(color: _kBorder),
                          ),
                        ),
                        validator: (v) => (v == null || !v.contains('@')) ? 'Email invalide' : null,
                      ),
                      const SizedBox(height: 16),

                      // Password
                      const Text('Mot de passe',
                          style: TextStyle(fontFamily: 'Inter', fontSize: 13, fontWeight: FontWeight.w600, color: _kNavy)),
                      const SizedBox(height: 6),
                      TextFormField(
                        controller: _passCtrl,
                        obscureText: _obscure,
                        style: const TextStyle(fontFamily: 'Inter', fontSize: 14),
                        decoration: InputDecoration(
                          hintText: '••••••••',
                          prefixIcon: const Icon(Icons.lock_outline, size: 20, color: _kMuted),
                          suffixIcon: IconButton(
                            icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                                size: 20, color: _kMuted),
                            onPressed: () => setState(() => _obscure = !_obscure),
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(6),
                            borderSide: const BorderSide(color: _kBorder),
                          ),
                        ),
                        validator: (v) => (v?.length ?? 0) < 6 ? 'Minimum 6 caractères' : null,
                      ),
                      const SizedBox(height: 24),

                      // Submit button
                      SizedBox(
                        width: double.infinity,
                        height: 46,
                        child: ElevatedButton(
                          onPressed: _loading ? null : _submit,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: _kBlue,
                            foregroundColor: Colors.white,
                            elevation: 0,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                          ),
                          child: _loading
                              ? const SizedBox(
                                  width: 20, height: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                              : const Text('Se connecter',
                                  style: TextStyle(fontFamily: 'Inter', fontSize: 15, fontWeight: FontWeight.w600)),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),

          // Footer
          Padding(
            padding: const EdgeInsets.only(bottom: 20),
            child: Text('FleetControl v1.0 · Gestion de flotte',
                style: TextStyle(fontFamily: 'Inter', fontSize: 11, color: _kMuted.withOpacity(0.7))),
          ),
        ],
      ),
    );
  }
}
