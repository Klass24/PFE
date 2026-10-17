part of 'document_bloc.dart';

abstract class DocumentState {}

class DocumentInitial extends DocumentState {}

class DocumentLoading extends DocumentState {}

class DocumentsLoaded extends DocumentState {
  final List<Document> documents;

  DocumentsLoaded(this.documents);
}

class DocumentError extends DocumentState {
  final String message;

  DocumentError(this.message);
}
